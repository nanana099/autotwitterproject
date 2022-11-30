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
use App\Exceptions\TwitterAuthExipiredException;

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
                account_settings.bool_unfollow_inactive,
                operation_statuses.is_unfollow
            FROM accounts
            INNER JOIN account_settings
                ON accounts.id = account_settings.account_id
            INNER JOIN operation_statuses
                ON accounts.id = operation_statuses.account_id
                AND operation_statuses.is_flozen = 0
                AND operation_statuses.is_unfollow = 1
                AND operation_statuses.unfollow_stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
        logger()->info('UnfollowExecutor：prepare-end'.' 対象件数（アカウント）：'.count($this->accounts));
    }

    // 実行
    public function execute()
    {
        logger()->info('UnfollowExecutor：execute-start');
        foreach ($this->accounts as $account) {
            try {
                // Twitterアカウントのインスタンス作成
                $twitterAccount = new TwitterAccount($account->access_token);
                $accountFromDB = Account::find($account->id);
                // アカウントの設定情報
                $operationStatus = $accountFromDB->operationStatus;
                // アカウントを所持するユーザー
                $user = $accountFromDB->user()->get()[0];
                // 前回までの進捗
                $cursor = $operationStatus->unfollowing_target_cursor;
                // フォロー済みアカウント格納用
                $followedAccounts = [];

                try {
                    try {
                        do {
                            // 0:カーソルの終点 -1：カーソルの始点（前回のフォローリスト参照が完了した場合、 「0」になることがあるので、始点に移動）
                            if ($cursor ==="0") {
                                $cursor = "-1";
                            }
                        
                            // アンフォローするアカウント
                            $unfollowTargetAccounts = [];

                            // フォロー中のアカウントを取得
                            $response = $twitterAccount->getMyFollowedList($cursor);
                            $followedAccounts = array_merge($followedAccounts, empty($response['ids']) ? [] : $response['ids']);

                            // アンフォローするアカウントを抽出
                            $unfollowTargetAccounts = $this->getUnFollowTargetAccounts($followedAccounts, $account);

                            // アンフォロー
                            $this->unfollow($unfollowTargetAccounts, $twitterAccount, $account);
                        } while ($cursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));

                        // 進捗情報をクリア
                        $operationStatus->fill(array('unfollowing_target_cursor' => "-1"))->save();
                    } catch (Exception $e) {
                        // 進捗情報をDBに格納
                        $operationStatus->fill(array('unfollowing_target_cursor' => $cursor,'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
                        throw $e;
                    }
                    // すべてのターゲットアカウントに対する処理が終了した場合
                    $operationStatus->fill(array( 'is_unfollow' => 0,
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();

                    $count = self::getUnfollowedCount($account->id);

                    $mailMsg = <<< EOM
%s

なお、現在の累計アンフォロー件数は%d件です。

※設定内容やアンフォロー機能のタイミング次第では、アンフォローを行わない場合もございますのでご了承ください。
例：「設定」画面にて、「フォローしてから「7」日間フォローが返ってこない場合にアンフォローする 」とご指定の場合で、フォローから7日経過していない場合など                 
EOM;
                    $mailMsg = sprintf($mailMsg, $twitterAccount->getScreenName(), $count);
                    MailSender::send($user->name, $mailMsg, $user->email, MailSender::EMAIL_UNFOLLOW_COMPLATED);
                } catch (TwitterRestrictionException $e) {
                    // APIの回数制限
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    $operationStatus->fill(array(
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
                } catch (TwitterFlozenException $e) {
                    // 凍結
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                    $operationStatus->fill(array('is_follow' => 0,
                    'is_unfollow' => 0,
                    'is_favorite' => 0,
                    'is_flozen'=>1,
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FLOZEN);
                }catch (TwitterAuthExipiredException $e) {
                    // 凍結
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                    $operationStatus->fill(array('is_follow' => 0,
                    'is_unfollow' => 0,
                    'is_favorite' => 0,
                    'is_flozen'=>1,
                    'unfollow_stopped_at' => date('Y/m/d H:i:s')))->save();
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::AUTH_EXIPIRED);
                }
            } catch (Exception $e) {
                // どんな例外があっても次のアカウントの処理をするために、ここでExceptionをキャッチする
                logger()->error($e);
            }
        }

        logger()->info('UnfollowExecutor：execute-end');
    }

    private function getUnfollowedCount($account_id)
    {
        $count = 0;
        $count = DB::table('unfollowed_users')
                    ->select(DB::raw('count(*) count'))
                    ->where('account_id', '=', $account_id)
                    ->get()[0]->count;
        return $count;
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

        // 非アクティブ基準のアンフォロー実行はユーザーが設定できる
        if ($account->bool_unfollow_inactive) {
            // 非アクティブのアカウントをアンフォローする
            $this->unfollowBasedOnActiveStatus($twitterAccount, $unfollowTargetAccouts, $account->days_inactive_user, $account->id);
        }
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
