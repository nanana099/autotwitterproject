<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Account;
use App\FollowedUser;
use App\OperationStatus;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;

// 自動フォロー実行クラス
class FollowExecutor implements ITwitterFunctionExecutor
{
    // 自動フォロー実行アカウント
    private $accounts = [];

    // 自動フォローを行うための準備
    public function prepare()
    {
        logger()->info('FollowExecutor：prepare-start');
        // 自動フォロー実行アカウント取得
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

    // 自動フォローを実行
    public function execute()
    {
        logger()->info('FollowExecutor：execute-start');

        foreach ($this->accounts as  $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            // ユーザーが設定したフォローキーワード
            $keywords = empty($account->keyword_follow) ? [] : explode(',', $account->keyword_follow);
            // ユーザーが設定したターゲットアカウント
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
                            // 処理中のターゲットアカウントをDBに保存（次回の実行時に使う）
                            $operationStatus->fill(array('following_target_account' => $targetAccount))->save();

                            // ターゲットアカウントのフォロワーを取得
                            $this->getFollowers($targetAccount, $twitterAccount, $prevTargetAccountCursor, $targetAccountFollowers);

                            // cursorを一番最初にする
                            $operationStatus->fill(array('following_target_account_cursor' => "-1"))->save();
                        } catch (Exception $e) {
                            // 進捗情報をDBに記録
                            $operationStatus->fill(array('following_target_account_cursor' => $prevTargetAccountCursor))->save();
                            // 
                            OperationStatus::where('account_id', $account->id)->first()->fill(array(
                                        'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
                            throw $e;
                        }
                    } finally {
                        // ターゲットアカウントのフォロワー取得中にAPI制限にかかる場合でもフォロー処理を行いたいのでfinally句に処理を記述

                        // フォローするアカウントを抽出
                        $followUsers = $this->getFollowUsers($targetAccountFollowers, $followedUsers, $unfollowedUsers, $keywords);

                        // フォロー実行
                        foreach ($followUsers as $followUser) {
                            $twitterAccount->follow($followUser);

                            // フォローできたアカウントをDBに登録
                            FollowedUser::updateOrCreate(array('user_id' => $followUser, 'account_id' => $account->id), array('followed_at' => Carbon::now()));
                        }
                    }
                }
                // すべてのターゲットアカウントに対する処理が終了した場合
                $operationStatus->fill(array('following_target_account' => "",'following_target_account_cursor' => "-1"))->save();
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_follow' => 0,
                    'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterRestrictionException $e) {
                // APIの回数制限
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (TwitterFlozenException $e) {
                // 凍結
                // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                'is_follow' => 0,
                'is_flozen'=>1,
                'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
            } catch (Exception $e) {
                logger()->error($e);
            }
        }
        logger()->info('FollowExecutor：execute-end');
    }

    // ターゲットアカウントのフォロワーを取得する
    private function getFollowers(string $targetAccount, TwitterAccount $twitterAccount, string &$cursor, array &$followers)
    {
        // ターゲットアカウントのフォロワー取得（フォロワーリスト）
        // １回のリクエストで200件のフォロワーしか取れないので、最後のフォロワーに行き着くまでループする。
        // なお、途中でAPIの回数制限になった場合は、次回実行時に途中から再開できるように、
        // 当関数の呼び元で、「処理中のターゲットアカウント」「ターゲットアカウントのフォロワーのカーソル」をDBに保管する。
        do {
            $response = $twitterAccount->getFollowerList($targetAccount, $cursor);
            $followers = array_merge($followers, empty($response['users']) ? [] : $response['users']);
        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));

        // 全件完了すると、"0"になる。次回の取得時にそのまま使うと、一覧が取得できなくなるので、一番最初のカーソルの"-1"を入れておく
        if($cursor === "0"){
            $cursor = "-1";
        }
    }

    // フォロー対象のアカウントを、アカウントリストから抽出する。
    private function getFollowUsers(array $followers, Collection $followedUsers, Collection $unfollowedUsers, array $keywords): array
    {
        $resultList =[];
        foreach ($followers as $targetAccountFollower) {
            $isContinue = false;
            // Todo:フォロー済みあかうんとをTwitterAPIでとりたい→フォロー済みは、アカウント登録時に、全部テーブルにINSERTしちゃうか？
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
