<?php
namespace App\Services;

use App\Account;
use Illuminate\Support\Facades\DB;
use \Exception;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\FollowedUser;
use Illuminate\Support\Carbon;
use App\OperationStatus;
use Illuminate\Database\Eloquent\Collection;

class FollowExecutor implements ITwitterFunctionExecutor
{
    private $accounts = [];
    public function prepare()
    {
        logger()->info('FollowExecutor：prepare-start');
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
                AND operation_statuses.follow_stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
        logger()->info('FollowExecutor：prepare-end'.' 対象件数（アカウント）：'.count($this->accounts));
    }

    public function execute()
    {
        logger()->info('FollowExecutor：execute-start');
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
                        } catch (Exception $e) {
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
                            $twitterAccount->follow($followUser);

                            // フォローできたらDBへ格納
                            // (new FollowedUser(array('user_id' => $followUser, 'account_id' => $account->id, 'followed_at' => Carbon::now())))->save();
                            FollowedUser::updateOrCreate(array('user_id' => $followUser, 'account_id' => $account->id), array('followed_at' => Carbon::now()));
                        }
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                'is_follow' => 0,
                'is_flozen'=>1,
                'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch(Exception $e){
                logger()->error($e);
            }
        }
        logger()->info('FollowExecutor：execute-end');
    }

    // 引数のターゲットアカウントのフォロワーを取得する
    private function getFollowers(string $targetAccount, TwitterAccount $twitterAccount, string &$cursor, array &$followers)
    {
        // ターゲットアカウントのフォロワー取得（フォロワーリスト）
        // １回のリクエストで200件のフォロワーしか取れないので、最後のフォロワーに行き着くまでループする。
        // なお、利用しているAPIは15分に15回までしか呼べないため、制限にかかる（＝TwitterRestrictionExceptionの発生）場合は、
        // 当関数の呼び元で、「処理中のターゲットアカウント」「ターゲットアカウントのフォロワーのカーソル」をDBに保管しておいて、
        // 次回の自動フォロー起動時に続きからできるようにする。
        do {
            $response = $twitterAccount->getFollowerList($targetAccount, $cursor);
            $followers = array_merge($followers, empty($response['users']) ? [] : $response['users']);
        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));
    }

    // 引数のターゲットアカウントのフォロワーのうち、フォロー対象のアカウントを取得する
    private function getFollowUsers(array $followers, Collection $followedUsers, Collection $unfollowedUsers, array $keywords): array
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
