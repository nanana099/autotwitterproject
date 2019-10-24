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
            // 前回までにすすんだステップ
            $unfollowing_step = $operationStatus->unfollowing_step;
            // 前回最後にアンフォローしたアカウント
            $currentTargetUser = $operationStatus->unfollowing_target_account;

            try {
                logger(1);
                // Todo:現在のフォロー中のリストを撮ってきたほうが良いな。２回目以降も↓のレコードにはアンフォロー済みのアカウントが入ってくる。フォローしてから○日は、↓と照らし合わせて存在していれば、実行するように仕様。
                // アンフォロー対象経過日時以上のアカウントリスト  形式：["1234556789","987654321","...]
                $unfollowTargetUsers = json_decode(Account::find($account->id)->followedUsers()->where('followed_at', '<=', Carbon::today()->subDay($account->days_unfollow_user))->orderBy('user_id', 'asc')->get(['user_id'])->pluck('user_id'));
                logger(2);
                // 重複は削除しておく
                $unfollowTargetUsers = array_values(array_unique($unfollowTargetUsers));
                logger(3);

                // STEP0：フォロー返しがないユーザーをアンフォローする
                if ($unfollowing_step === 0) {
                    logger(4);
                    try {
                        $unfollowedUsers = [];
                        $this->unfollowBasedOnFollowedBy($twitterAccount, $unfollowTargetUsers, $account->id, $unfollowedUsers, $currentTargetUser);
                        // アンフォロー済みのユーザーをターゲットのリストから削除しておく
                        $unfollowTargetUsers = array_values(array_diff($unfollowTargetUsers, $unfollowedUsers));
                        logger(5);
    
                        // ステップを進める
                        $unfollowing_step++;
                        $currentTargetUser = "";
                        $operationStatus->fill(array('unfollowing_step' => $unfollowing_step,'unfollowing_target_account' => $currentTargetUser))->save();
                        logger(6);
                    } catch (TwitterRestrictionException $e) {
                        logger(7);
                        // 次回途中から始めるために、進捗をDBに保存
                        $operationStatus->fill(array('unfollowing_step' => $unfollowing_step,'unfollowing_target_account' => $currentTargetUser))->save();
                        throw $e;
                    }
                }

                // STEP1：非アクティブなユーザーをアンフォローする
                if ($unfollowing_step === 1) {
                    logger(8);
                    try {
                        $this->unfollowBasedOnActiveStatus($twitterAccount, $unfollowTargetUsers, $account->days_inactive_user, $account->id, $currentTargetUser);
                        logger(9);
    
                        // ステップを最初に戻す
                        $unfollowing_step = 0;
                        $currentTargetUser = "";
                        $operationStatus->fill(array('unfollowing_step' => $unfollowing_step,'unfollowing_target_account' => $currentTargetUser))->save();
                        logger(10);
                    } catch (TwitterRestrictionException $e) {
                        logger(11);
                        // 次回途中から始めるために、進捗をDBに保存
                        $operationStatus->fill(array('unfollowing_step' => $unfollowing_step,'unfollowing_target_account' => $currentTargetUser))->save();
                        throw $e;
                    }
                }
            } catch (TwitterRestrictionException $e) {
                logger(12);
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
            }
        }
    }

    // フォロー返しの有無に基づいて、アンフォローを実行
    private function unfollowBasedOnFollowedBy(TwitterAccount $twitterAccount, $unfollowTargetUsers, $account_id, &$unfollowedUsers, &$currentTargetUser)
    {
        // 処理済みのターゲットアカウントを配列から削除しておく
        $shurinkedUnfollowTargetUsers = array_slice($unfollowTargetUsers, array_search($currentTargetUser, $unfollowTargetUsers));
        // 100はTwitterAPIの'friendships/lookup'に渡せる引数の最大個数
        $chunkedUnfollowUsers = array_chunk($shurinkedUnfollowTargetUsers, 100);

        foreach ($chunkedUnfollowUsers as $val) {
            $csv = implode(',', $val);
            // ユーザーとの関係を取得
            $friendships = $twitterAccount->getFriendShips($csv);

            // ユーザーごとのループ
            foreach ($friendships as $friendship) {
                $isFollowed = false;
                // フォロー返しがあるか判定
                foreach ($friendship->connections as $connection) {
                    if ($connection === 'followed_by') {
                        $isFollowed = true;
                    }
                }
                // アンフォロー実施
                if (!$isFollowed) {
                    // アンフォローリストに追加(DB)
                    $twitterAccount->unfollow($friendship->id_str);
                    $currentTargetUser = $friendship->id_str;
                    (new UnfollowedUser(array('user_id' => $friendship->id_str, 'account_id' => $account_id)))->save();
                    $unfollowedUsers[] = $friendship->id_str;
                }
            }
        }
    }

    // ユーザーが非アクティブであればアンフォローを実行
    private function unfollowBasedOnActiveStatus(TwitterAccount $twitterAccount, $unfollowTargetUsers, $activeLimitDate, $account_id, &$currentTargetUser)
    {
        // 処理済みのターゲットアカウントを配列から削除しておく
        $shurinkedUnfollowTargetUsers = array_slice($unfollowTargetUsers, array_search($currentTargetUser, $unfollowTargetUsers));
        $unfollowedUsers = [];
        foreach ($shurinkedUnfollowTargetUsers as $unfollowTargetUser) {
            // 非アクティブか判定
            $latestTweetDateTime = new DateTime($twitterAccount->getLatestTweetDate($unfollowTargetUser));
            $limitDateTime = new DateTime("-".$activeLimitDate." day");// 例えば "-3 day" とすると、３日前のdatetimeが得られる
            logger(13);
            if ($latestTweetDateTime > $limitDateTime) {
                // アンフォロー実施
                // アンフォローリストに追加(DB)
                logger(14);
                $twitterAccount->unfollow($unfollowTargetUser);
                $currentTargetUser = $unfollowTargetUser;
                (new UnfollowedUser(array('user_id' => $unfollowTargetUser, 'account_id' => $account_id)))->save();
                $unfollowedUsers[] = $unfollowTargetUser;
                logger(15);
            }
        }
        return $unfollowedUsers;
    }
}
