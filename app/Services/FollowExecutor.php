<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Account;
use App\FollowedUser;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterAuthExipiredException;

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
            try {
                // Twitterアカウントのインスタンス作成
                $twitterAccount = new TwitterAccount($account->access_token);
                // ユーザーが設定したフォローキーワード
                $keywords = empty($account->keyword_follow) ? [] : explode(',', $account->keyword_follow);
                // ユーザーが設定したターゲットアカウント
                $targetAccounts = empty($account->target_accounts) ? [] : explode(',', $account->target_accounts);
                $accountFromDB = Account::find($account->id);
                // アカウントを所持するユーザー
                $user = $accountFromDB->user()->get()[0];
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
                            // 処理中のターゲットアカウントをDBに保存（次回の実行時に使う）
                            $operationStatus->fill(array('following_target_account' => $targetAccount))->save();
                            do {
                                // 0:カーソルの終点 -1：カーソルの始点（前回のターゲットアカウントのフォロワー参照完了時 「0」になるので、始点に移動）
                                if ($prevTargetAccountCursor === "0") {
                                    $prevTargetAccountCursor = "-1";
                                }

                                // ターゲットアカウントのフォロワー
                                $targetAccountFollowers = [];

                                // ターゲットアカウントのフォロワー取得（最大200件ごと）
                                $response = $twitterAccount->getFollowerList($targetAccount, $prevTargetAccountCursor);
                                $targetAccountFollowers = array_merge($targetAccountFollowers, empty($response['users']) ? [] : $response['users']);
                                
                                // フォローするアカウントを抽出
                                $followUsers = $this->getFollowUsers($targetAccountFollowers, $followedUsers, $unfollowedUsers, $keywords);

                                foreach ($followUsers as $followUser) {
                                    // フォロー実行
                                    $twitterAccount->follow($followUser);

                                    // フォローできたアカウントをDBに登録
                                    FollowedUser::updateOrCreate(array('user_id' => $followUser, 'account_id' => $account->id), array('followed_at' => Carbon::now()));
                                }
                            } while ($prevTargetAccountCursor = (empty($response['next_cursor_str']) ? "0" : $response['next_cursor_str']));

                            // ターゲットアカウントのフォロワーをすべて参照し終えたら、カーソルを先頭に戻す
                            $operationStatus->fill(array('following_target_account' => "",'following_target_account_cursor' => "-1"))->save();
                        } catch (Exception $e) {
                            // ターゲットアカウントのフォロワーリスト取得 or フォロー実行で失敗
                            // 進捗情報をDBに記録
                            $operationStatus->fill(array('following_target_account_cursor' => $prevTargetAccountCursor, 'follow_stopped_at' => date('Y/m/d H:i:s')))->save();
                            throw $e;
                        }
                    }

                    // すべてのターゲットアカウントに対する処理が終了した場合
                    $operationStatus->fill(array('following_target_account' => "",
                            'following_target_account_cursor' => "-1",
                            'is_follow' => 0,
                            'follow_stopped_at' => date('Y/m/d H:i:s')
                            ))->save();

                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FOLLOW_COMPLATED);
                } catch (TwitterRestrictionException $e) {
                    // APIの回数制限
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    $operationStatus->fill(array('follow_stopped_at' => date('Y/m/d H:i:s')
                                                ))->save();
                } catch (TwitterFlozenException $e) {
                    // 凍結
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                    $operationStatus->fill(array('is_follow' => 0,
                                            'is_unfollow' => 0,
                                            'is_favorite' => 0,
                                            'is_flozen'=>1,
                                            'follow_stopped_at' => date('Y/m/d H:i:s')
                                                ))->save();
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FLOZEN);
                } catch (TwitterAuthExipiredException $e) {
                    $operationStatus->fill(array('is_follow' => 0,
                                            'is_unfollow' => 0,
                                            'is_favorite' => 0,
                                            'is_flozen'=>1,
                                            'follow_stopped_at' => date('Y/m/d H:i:s')
                                                ))->save();
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::AUTH_EXIPIRED);
                }
            } catch (Exception $e) {
                // どんな例外があっても、次のアカウントの処理をするため、ここでExceptinをキャッチする
                logger()->error($e);
            }
        }
        logger()->info('FollowExecutor：execute-end');
    }

    // フォロー返しする
    private function followFollower($twitterAccount)
    {
        // フォロワー
        $followers = $twitterAccount->getFollowerIds($twitterAccount->getScreenName(), -1, 5000)['ids'] ;
        // フォロー済みユーザー
        $followed = $twitterAccount->getFollowedUsers(-1, 5000)['ids'];// max5000の件取得
        // フォロー申請しないユーザー（鍵垢とかは毎回申請してしまうので、この配列に入れる。（本当は自動化したい）
        $exclude = ['1060376302908100608','1144550229540331520','2896062624','922632418464374785','935175772964139008'];
        foreach ($followers as $follower) {
            if (in_array($follower, $exclude)) {
                continue;
            }
            if (!in_array($follower, $followed)) {
                // フォロー実行
                $twitterAccount->follow($follower);
            }
        }
    }

    // フォロー対象のアカウントを、アカウントリストから抽出する。
    private function getFollowUsers(array $followers, Collection $followedUsers, Collection $unfollowedUsers, array $keywords): array
    {
        // フォロー対象アカウントのID格納用
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
                // 五十音が入っている場合のみ
                if (preg_match("/[ぁ-ん]+|[ァ-ヴー]+/u", $targetAccountFollower->description)) {
                    // ユーザーが指定したキーワードに合致するか調べ、フォロー対象か判定する
                    if (self::match($targetAccountFollower->description, $keyword)) {
                        $resultList[] = $targetAccountFollower->id_str;
                        break;
                    }
                }
            }
        }
        return $resultList;
    }
  
    // $target:検査対象の文字列
    // $keyword:ユーザーが指定したキーワード
    public static function match($target, $keyword)
    {
        $notStr = '';
        $orStr = '';
        $andStr = '';
        KeywordOperatorAnalyzer::operatorStrToCSV($keyword, $andStr, $orStr, $notStr);
        $andAry = empty($andStr)? [] :explode(',', $andStr);
        $orAry = empty($orStr)? [] :explode(',', $orStr);
        $notAry = empty($notStr)? [] :explode(',', $notStr);

        foreach ($notAry as $str) {
            if (preg_match("/".$str."/u", $target)) {
                // １件でも引っかかったらfalse
                return false;
            }
        }

        foreach ($andAry as $str) {
            if (preg_match("/".$str."/u", $target)) {
            } else {
                // １件でも引っかからない場合はfalse
                return false;
            }
        }

        if (count($orAry) === 0) {
            return true;
        }

        foreach ($orAry as $str) {
            if (preg_match("/".$str."/u", $target)) {
                // １件でも引っかかればtrue
                return true;
            }
        }
        return false;
    }
}
