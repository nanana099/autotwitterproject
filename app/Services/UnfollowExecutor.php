<?php
namespace App\Services;

use App\Account;
use Illuminate\Support\Facades\DB;
use \Exception;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\UnfollowedUser;
use Illuminate\Support\Carbon;
use \DateTime;
use App\OperationStatus;

class UnfollowExecutor implements ITwitterFunctionExecutor
{
    private $accounts = [];
    public function prepare()
    {
        // 対象リストの作成
        // フォロワー数が5000以上 または アンフォローが「稼働中」のアカウントは、自動アンフォロー実行対象のアカウント
        $this->accounts = DB::select(
            'SELECT
                accounts.id,
                accounts.access_token,
                account_settings.days_inactive_user,
                account_settings.days_unfollow_user,
                operation_statuses.is_unfollow
            FROM accounts
            INNER JOIN account_settings
                ON accounts.id = account_settings.account_id
            INNER JOIN operation_statuses
                ON accounts.id = operation_statuses.account_id
                AND operation_statuses.is_flozen = 0
                AND operation_statuses.unfollow_stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
        foreach ($this->accounts as $key => $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            try {
                if ($twitterAccount->getMyFollowersCount() <= 5000) {
                    if (!$account->is_unfollow) {
                        // アカウントが強制起動条件（フォローが5000超え)でも「稼働中」でもない場合は、処理対象から外す
                        unset($this->accounts[$key]);
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                'is_unfollow' => 0,
                'is_flozen'=>1,
                'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (Exception $e) {
                // その他例外
                logger($e);
            }
        }
    }


    public function execute()
    {
        foreach ($this->accounts as $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            // アカウントの設定情報
            $operationStatus = Account::find($account->id)->operationStatus;
            // 前回までの進捗
            $cursor = $operationStatus->unfollowing_target_cursor;
            // フォロー済みアカウント格納用
            $followedAccounts = [];

            try {
                // フォロー済みアカウントを取得
                try {
                    $this->getFollowedAccounts($cursor, $followedAccounts, $twitterAccount);
                    // 処理中情報をクリア
                    $operationStatus->fill(array('unfollowing_target_cursor' => "-1"))->save();
                } catch (Exception $e) {
                    // 処理中情報をDBに格納
                    $operationStatus->fill(array('unfollowing_target_cursor' => $cursor))->save();
                    throw $e;
                } finally {
                    // アンフォロー候補のアカウントを取得
                    $unfollowTargetAccounts = $this->getUnFollowTargetAccounts($followedAccounts, $account);

                    // アンフォロー
                    $this->unfollow($unfollowTargetAccounts, $twitterAccount, $account);
                }
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_unfollow' => 0,
                    'is_flozen'=>1,
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (Exception $e) {
                // その他例外
                logger($e);
            }
        }
    }

    // フォロー済みアカウントを取得する
    private function getFollowedAccounts(string &$cursor, array &$followedAccounts, TwitterAccount $twitterAccount)
    {
        do {
            $response = $twitterAccount->getMyFollowedList($cursor);
            $followedAccounts = array_merge($followedAccounts, empty($response['ids']) ? [] : $response['ids']);
        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));
    }

    // アンフォロー候補のアカウントを取得する
    private function getUnFollowTargetAccounts($followedAccounts, $account)
    {
        // フォローしてからn日経過しているアカウントのみ対象とする。（フォローチャーン対策）
        // memo:TwitterAPIからフォローした日時を取得する手段がないため、システム上フォロー日時がわかるアカウントだけを対象とする。
        $unfollowTargetAccounts = [];
        $unfollowTargetAccounts = json_decode(Account::find($account->id)->followedUsers()->where('followed_at', '<=', Carbon::today()->subDay($account->days_unfollow_user))->get(['user_id'])->pluck('user_id'));
        $unfollowTargetAccounts = array_values(array_intersect($followedAccounts, $unfollowTargetAccounts));
        return $unfollowTargetAccounts;
    }

    // アンフォローを実行する
    private function unfollow($unfollowTargetAccouts, TwitterAccount $twitterAccount, $account)
    {
        // フォロー返しがないアカウントをアンフォローする
        $unfollowedAccounts = $this->unfollowBasedOnFollowedBy($twitterAccount, $unfollowTargetAccouts, $account->id);

        // アンフォロー済みのユーザーをターゲットのリストから削除
        $unfollowTargetAccouts = array_values(array_diff($unfollowTargetAccouts, $unfollowedAccounts));

        // 非アクティブのアカウントをアンフォローする
        $this->unfollowBasedOnActiveStatus($twitterAccount, $unfollowTargetAccouts, $account->days_inactive_user, $account->id);
    }

    // フォロー返しの有無に基づいて、アンフォローを実行
    private function unfollowBasedOnFollowedBy(TwitterAccount $twitterAccount, $unfollowTargetUsers, $account_id)
    {
        // アンフォロー済みアカウントのID格納用
        $unfollowedAccounts = [];

        // 100はTwitterAPIの'friendships/lookup'に渡せる引数の最大個数
        $chunkedUnfollowUsers = array_chunk($unfollowTargetUsers, 100);

        foreach ($chunkedUnfollowUsers as $val) {
            $csv = implode(',', $val);
            // ユーザーとの関係を取得
            $friendships = $twitterAccount->getFriendShips($csv);

            // ユーザーごとのループ
            foreach ($friendships as $friendship) {
                // フォロー返しがあるか判定
                foreach ($friendship->connections as $connection) {
                    $isFollowed = $connection === 'followed_by' ;
                }

                // アンフォロー実行
                if (!$isFollowed) {
                    $twitterAccount->unfollow($friendship->id_str);
                    (new UnfollowedUser(array('user_id' => $friendship->id_str, 'account_id' => $account_id)))->save();
                    $unfollowedAccounts[] = $friendship->id_str;
                }
            }
        }
        return $unfollowedAccounts;
    }

    // 非アクティブのアカウントをアンフォローする
    private function unfollowBasedOnActiveStatus(TwitterAccount $twitterAccount, $unfollowTargetAccounts, $activeLimitDate, $account_id)
    {
        foreach ($unfollowTargetAccounts as $unfollowTargetAccount) {
            // 非アクティブか判定
            $latestTweetDateTime = new DateTime($twitterAccount->getLatestTweetDate($unfollowTargetAccount));
            $limitDateTime = new DateTime("-".$activeLimitDate." day");// 例えば "-3 day" とすると、３日前のdatetimeが得られる
            $isActive = $latestTweetDateTime > $limitDateTime; // 最新ツイート日時 > 非アクティブ日時

            // アンフォロー実行
            if (!$isActive) {
                $twitterAccount->unfollow($unfollowTargetAccount);
                (new UnfollowedUser(array('user_id' => $unfollowTargetAccount, 'account_id' => $account_id)))->save();
            }
        }
    }
}
