<?php
namespace App\Services;

use \DateTime;
use \Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Account;
use App\OperationStatus;
use App\UnfollowedUser;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterRestrictionException;

// 自動アンフォロー実行クラス
class UnfollowExecutor implements ITwitterFunctionExecutor
{
    // 自動アンフォロー実行アカウント
    private $accounts = [];

    // 準備
    public function prepare()
    {
        logger()->info('UnfollowExecutorr：prepare-start');
        // 対象リストの作成
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
        // 条件：フォロワー数が5000以上 または アンフォローが「稼働中」
        foreach ($this->accounts as $key => $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            try {
                if ($twitterAccount->getMyFollowersCount() <= 5000) {
                    if (!$account->is_unfollow) {
                        // 対象から外す
                        unset($this->accounts[$key]);
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                'is_unfollow' => 0,
                'is_flozen'=>1,
                'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (Exception $e) {
                // その他例外
                logger()->error($e);
            }
        }
        logger()->info('UnfollowExecutor：prepare-end'.' 対象件数（アカウント）：'.count($this->accounts));
    }

    // 実行
    public function execute()
    {
        logger()->info('UnfollowExecutor：execute-start');
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
                try {
                    // フォロー中のアカウントを取得
                    $this->getFollowedAccounts($cursor, $followedAccounts, $twitterAccount);
                    // 進捗情報をクリア
                    $operationStatus->fill(array('unfollowing_target_cursor' => "-1"))->save();
                } catch (Exception $e) {
                    // 進捗情報をDBに格納
                    $operationStatus->fill(array('unfollowing_target_cursor' => $cursor))->save();
                    throw $e;
                } finally {
                    // フォロー中のアカウント取得中にAPI制限にかかる場合でもアンフォロー処理を行いたいのでfinally句に処理を記述

                    // アンフォローするアカウントを抽出
                    $unfollowTargetAccounts = $this->getUnFollowTargetAccounts($followedAccounts, $account);

                    // アンフォロー
                    $this->unfollow($unfollowTargetAccounts, $twitterAccount, $account);
                }
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_unfollow' => 0,
                    'is_flozen'=>1,
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (Exception $e) {
                // その他例外
                logger()->error($e);
            }
        }

        logger()->info('UnfollowExecutor：execute-end');
    }

    // フォロー中のアカウントを取得する
    private function getFollowedAccounts(string &$cursor, array &$followedAccounts, TwitterAccount $twitterAccount)
    {
        do {
            $response = $twitterAccount->getMyFollowedList($cursor);
            $followedAccounts = array_merge($followedAccounts, empty($response['ids']) ? [] : $response['ids']);
        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));
    }

    // アンフォローするアカウントを取得する
    private function getUnFollowTargetAccounts($followedAccounts, $account)
    {
        // 条件：フォローしてからn日経過している
        // memo:フォローチャーン対策として、n日経過を待つ。
        //      TwitterAPIからフォローした日時を取得する手段がないため、システム上フォロー日時がわかるアカウントだけを対象とする。
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
                    // アンフォローできたアカウントはDBに記録しておく
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
                // アンフォローできたアカウントはDBに記録しておく
                (new UnfollowedUser(array('user_id' => $unfollowTargetAccount, 'account_id' => $account_id)))->save();
            }
        }
    }
}
