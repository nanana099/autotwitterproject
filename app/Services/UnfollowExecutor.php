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
                AND operation_statuses.stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
        foreach ($this->accounts as $key => $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            try {
                if ($twitterAccount->getMyFollowersCount() < 5000) {
                    if (!$account->is_unfollow) {
                        // リストから除外
                        unset($this->accounts[$key]);
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // API制限
                // 処理を次のアカウントへ
                // 前回停止時間を更新
            } catch (TwitterFlozenException $e) {
                // 凍結
                // 処理を次のアカウントへ
                // 稼働フラグを0へ変更
                // 凍結フラグを1へ変更
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
            try {
                // アンフォロー対象経過日時以上のアカウントリスト  形式：["1234556789","987654321","...]
                $unfollowTargetUsers = json_decode(Account::find($account->id)->followedUsers()->where('followed_at', '<=', Carbon::today()->subDay($account->days_unfollow_user))->get(['user_id'])->pluck('user_id'));

                // フォロー返しがないユーザーをアンフォロー
                $unfollowedUsers = $this->unfollowBasedOnFollowedBy($twitterAccount, $unfollowTargetUsers, $account->id);

                // アンフォロー済みのユーザーをターゲットのリストから削除
                $unfollowTargetUsers = array_values(array_diff($unfollowTargetUsers, $unfollowedUsers));

                // 非アクティブユーザーをアンフォロー
                $this->unfollowBasedOnActiveStatus($twitterAccount, $unfollowTargetUsers, $account->days_inactive_user, $account->id);
            } catch (TwitterRestrictionException $e) {
                // API制限
                // 処理を次のアカウントへ
                // 前回停止時間を更新
            } catch (TwitterFlozenException $e) {
                // 凍結
                // 処理を次のアカウントへ
                // 稼働フラグを0へ変更
                // 凍結フラグを1へ変更
            } catch (Exception $e) {
                // その他例外
            }
        }
    }

    // フォロー返しの有無に基づいて、アンフォローを実行
    private function unfollowBasedOnFollowedBy(TwitterAccount $twitterAccount, $unfollowTargetUsers, $account_id)
    {
        $unfollowedUsers = [];
        // 100はTwitterAPIの'friendships/lookup'に渡せる引数の最大個数
        $chunkedUnfollowUsers = array_chunk($unfollowTargetUsers, 2);

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
                    (new UnfollowedUser(array('user_id' => $friendship->id_str, 'account_id' => $account_id)))->save();
                    $unfollowedUsers[] = $friendship->id_str;
                }
            }
        }
        return $unfollowedUsers;
    }

    // ユーザーが非アクティブであればアンフォローを実行
    private function unfollowBasedOnActiveStatus(TwitterAccount $twitterAccount, $unfollowTargetUsers, $activeLimitDate, $account_id)
    {
        $unfollowedUsers = [];
        foreach ($unfollowTargetUsers as $unfollowTargetUser) {
            // 非アクティブか判定
            $latestTweetDateTime = new DateTime($twitterAccount->getLatestTweetDate($unfollowTargetUser));
            $limitDateTime = new DateTime("-".$activeLimitDate." day");// 例えば "-3 day" とすると、３日前のdatetimeが得られる

            if ($latestTweetDateTime < $limitDateTime) {
                // アンフォロー実施
                // アンフォローリストに追加(DB)
                $twitterAccount->unfollow($unfollowTargetUser);
                (new UnfollowedUser(array('user_id' => $unfollowTargetUser, 'account_id' => $account_id)))->save();
                $unfollowedUsers[] = $unfollowTargetUser;
            }
        }
        return $unfollowedUsers;
    }
}
