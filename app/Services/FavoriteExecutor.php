<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Facades\DB;
use App\Account;
use App\OperationStatus;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterAuthExipiredException;

// 自動いいね実行クラス
class FavoriteExecutor implements ITwitterFunctionExecutor
{
    // 自動いいね実行アカウント
    private $accounts = [];
    // 準備
    public function prepare()
    {
        logger()->info('FavoriteExecutor：prepare-start');
        // 対象リストの作成
        $this->accounts = DB::select(
            'SELECT 
                accounts.id,
                accounts.access_token,
                account_settings.keyword_favorite
            FROM accounts 
            INNER JOIN account_settings 
                ON accounts.id = account_settings.account_id
            INNER JOIN operation_statuses 
                ON accounts.id = operation_statuses.account_id  
                AND operation_statuses.is_favorite = 1
                AND operation_statuses.is_flozen = 0
                AND operation_statuses.favorite_stopped_at <  SUBTIME(NOW(),\'00:15:00\')
                '
        );
        logger()->info('FavoriteExecutor：prepare-end'.' 対象件数（アカウント）：'.count($this->accounts));
    }

    // 実行
    public function execute()
    {
        logger()->info('FavoriteExecutor：execute-start');

        foreach ($this->accounts as  $account) {
            try {

            // Twitterアカウントのインスタンス作成
                $twitterAccount = new TwitterAccount($account->access_token);


                // ユーザーが設定したいいね対象のキーワード
                $keywords = empty($account->keyword_favorite) ? [] : explode(',', $account->keyword_favorite);

                try {
                    foreach ($keywords as $keyword) {
                        $notStr = '';
                        $orStr = '';
                        $andStr = '';
                        KeywordOperatorAnalyzer::operatorStrToCSV($keyword, $andStr, $orStr, $notStr);
                        $andAry = empty($andStr)? [] :explode(',', $andStr);
                        $orAry = empty($orStr)? [] :explode(',', $orStr);
                        $notAry = empty($notStr)? [] :explode(',', $notStr);
                        if (count($andAry) === 0 && count($orAry) === 0) {
                            break;
                        }

                        // つぶやきを検索
                        $count = 4;
                        $tweets = $twitterAccount->searchTweets($keyword, $count)['statuses'];
                        foreach ($tweets as $tweet) {
                            // いいね実行
                            $result = $twitterAccount->favorite($tweet->id_str);
                        }
                    }
                    
                    // $accountFromDB = Account::find($account->id);
                    // アカウントを所持するユーザー
                    // $user = $accountFromDB->user()->get()[0];
                    // すべてのターゲットアカウントに対する処理が終了した場合
                    // OperationStatus::where('account_id', $account->id)->first()->fill(array('is_favorite' => 0,
                    //                                                             'favorite_stopped_at' => date('Y/m/d H:i:s')
                    //                                                             ))->save();
                    // MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FAVORITE_COMPLATED);
                } catch (TwitterRestrictionException $e) {
                    // API制限
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'favorite_stopped_at' => date('Y/m/d H:i:s')))->save();
                    // メール送信
                } catch (TwitterFlozenException $e) {
                    // 凍結
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                    OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_follow' => 0,
                    'is_unfollow' => 0,
                    'is_favorite' => 0,
                    'is_flozen'=>1,
                    'favorite_stopped_at' => date('Y/m/d H:i:s')))->save();
                    // メール送信
                    $accountFromDB = Account::find($account->id);
                    // アカウントを所持するユーザー
                    $user = $accountFromDB->user()->get()[0];
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FLOZEN);
                } catch (TwitterAuthExipiredException $e) {
                    OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_follow' => 0,
                    'is_unfollow' => 0,
                    'is_favorite' => 0,
                    'is_flozen'=>1,
                    'favorite_stopped_at' => date('Y/m/d H:i:s')))->save();
                    // メール送信
                    $accountFromDB = Account::find($account->id);
                    // アカウントを所持するユーザー
                    $user = $accountFromDB->user()->get()[0];
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::AUTH_EXIPIRED);
                }
            } catch (Exception $e) {
                logger()->error($e);
            }
        }

        logger()->info('FavoriteExecutor：execute-end');
    }
}
