<?php
namespace App\Services;

use App\Account;
use Illuminate\Support\Facades\DB;
use \Exception;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\FollowedUser;
use Illuminate\Support\Carbon;

class FollowExecutor implements ITwitterFunctionExecutor
{
    private $accounts = [];
    public function prepare()
    {
        // 自動フォロー実行対象アカウント取得
        $this->accounts = DB::select(
            'SELECT accounts.id,
                    accounts.access_token,
                    account_settings.keyword_follow,
                    account_settings.target_accounts
            FROM accounts 
              INNER JOIN account_settings 
              ON accounts.id = account_settings.account_id
              INNER JOIN operation_statuses
              ON accounts.id = operation_statuses.account_id
                AND operation_statuses.is_follow = 1
                AND operation_statuses.is_flozen = 0
                AND operation_statuses.stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
    }

    public function execute()
    {
        foreach ($this->accounts as  $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            // フォローキーワード
            $keywords = empty($account->keyword_follow) ? [] : explode(',', $account->keyword_follow);
            // フォローターゲット
            $targetAccounts = empty($account->target_accounts) ? [] : explode(',', $account->target_accounts);
            $wk =Account::find($account->id);
            // フォロー済みリスト
            $followedUsers = $wk->followedUsers()->where('followed_at', '>=', Carbon::today()->subDay(30))->get();
            //アンフォロー済みリストをDBから取得
            $unfollowedUsers =  $wk->unfollowedUsers()->get();

            try {
                foreach ($targetAccounts as $targetAccount) {
                    // フォロー対象アカウントのリスト
                    $followUsers = $this->getFollowList($followedUsers, $unfollowedUsers, $keywords, $targetAccount, $twitterAccount);//['123456789','987654321','111111111',....]

                    // フォロー実行
                    foreach ($followUsers as $followUser) {
                        // フォローできたらDBへ格納
                        $twitterAccount->follow($followUser);
                        (new FollowedUser(array('user_id' => $followUser, 'account_id' => $account->id, 'followed_at' => Carbon::now())))->save();
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

    // フォロー対象アカウントのuser_idのリストを返す
    private function getFollowList($followedUsers, $unfollowedUsers, $keywords, $targetAccount, $twitterAccount)
    {
        $resultList = [];
        // ターゲットアカウントのフォロワー取得（フォロワーリスト）
        $targetAccountFollowers = $twitterAccount->getFollowerList($targetAccount)['users'];
        foreach ($targetAccountFollowers as $targetAccountFollower) {
            $isContinue = false;

            // 確認：フォロー済みでないか
            foreach ($followedUsers as $followedUser) {
                if ($followedUser->user_id === $targetAccountFollower->id_str) {
                    $isContinue = true;
                    break;
                }
            }
            if ($isContinue) {
                continue;
            }

            // 確認：アンフォロー済みでないか
            foreach ($unfollowedUsers as $unfollowedUser) {
                if ($unfollowedUser->user_id === $targetAccountFollower->id_str) {
                    $isContinue = true;
                    break;
                }
            }
            if ($isContinue) {
                continue;
            }

            // 確認：プロフィールにキーワードを含むか
            foreach ($keywords as $keyword) {
                if (preg_match("/[ぁ-ん]+|[ァ-ヴー]+/u", $targetAccountFollower->description) && (strpos($targetAccountFollower->description, $keyword) !== false)) {
                    $resultList[] = $targetAccountFollower->id_str;
                    break;
                }
            }
        }
        return $resultList;
    }
}
