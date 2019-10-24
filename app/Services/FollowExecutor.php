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
            $accountFromDB = Account::find($account->id);
            // フォロー済みリスト（３０日以内にフォロー済みのアカウント）
            $followedUsers = $accountFromDB->followedUsers()->where('followed_at', '>=', Carbon::today()->subDay(30))->get();
            // アンフォロー済みリストをDBから取得
            $unfollowedUsers =  $accountFromDB->unfollowedUsers()->get();
            // ターゲットアカウントのフォロワー格納用
            $targetAccountFollowers = [];
            // アカウントの設定情報
            $operationStatus = $accountFromDB->operationStatus;
            // 前回処理ターゲットアカウント
            $prevTargetAccount = $operationStatus->following_target_account;
            // 前回処理カーソル
            $prevTargetAccountCursor = $operationStatus->following_target_account_cursor;
            // 処理済みのターゲットアカウントを配列から削除しておく
            $targetAccounts = array_slice($targetAccounts, array_search($prevTargetAccount, $targetAccounts));
            try {
                foreach ($targetAccounts as $targetAccount) {
                    try {
                        try {
                            // ターゲットアカウントのフォロワーを取得
                            $this->getFollowers($targetAccount, $twitterAccount, $prevTargetAccountCursor, $targetAccountFollowers);
                            // 処理中情報をクリア
                            $operationStatus->fill(array('following_target_account' => "",'following_target_account_cursor' => "-1"))->save();
                        } catch (TwitterRestrictionException $e) {
                            // 処理中情報をDBに格納
                            $operationStatus->fill(array('following_target_account' => $targetAccount,'following_target_account_cursor' => $prevTargetAccountCursor))->save();
                            throw $e;
                        }
                    } finally {
                        // ターゲットアカウントのフォロワーを取得中にAPI制限にかかっても、取得できた分はフォロー処理をしたいので、以下の処理はfinallyに記述している
                        // フォロー対象を取得
                        $followUsers = $this->getFollowUsers($targetAccountFollowers, $followedUsers, $unfollowedUsers, $keywords);
                        // フォロー実行
                        foreach ($followUsers as $followUser) {
                            // フォローできたらDBへ格納
                            $twitterAccount->follow($followUser);
                            (new FollowedUser(array('user_id' => $followUser, 'account_id' => $account->id, 'followed_at' => Carbon::now())))->save();
                        }
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // 停止処理
                // ...
            }
        }
    }

    // 引数のターゲットアカウントのフォロワーを取得する
    private function getFollowers(string $targetAccount, TwitterAccount $twitterAccount, string &$cursor, array &$followers)
    {
        // ターゲットアカウントのフォロワー取得（フォロワーリスト）
        do {
            $response = $twitterAccount->getFollowerList($targetAccount, $cursor);
            $followers = array_merge($followers, empty($response['users']) ? [] : $response['users']);
        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));
    }

    // 引数のターゲットアカウントのフォロワーのうち、フォロー対象のアカウントを取得する
    private function getFollowUsers(array $followers, object $followedUsers, object $unfollowedUsers, array $keywords): array
    {
        $resultList =[];
        foreach ($followers as $targetAccountFollower) {
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

            // 確認：プロフィールにキーワードを含むか（５０音が入っていない場合はフォロー対象から除く）
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
