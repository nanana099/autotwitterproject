<?php
namespace App\Services;

// use App\Account;
// use App\AccountSetting;
// use Illuminate\Support\Facades\DB;
// use \Exception;
// use App\Exceptions\TwitterRestrictionException;
// use App\Exceptions\TwitterFlozenException;

class UnfollowExecutor implements ITwitterFunctionExecutor
{
    private $accounts = [];
    public function prepare()
    {
        logger('prepare1');
        // 対象リストの作成
        // 前回停止日時、凍結中は除く
        // $this->accounts = DB::select(
        //     'SELECT accounts.id,accounts.access_token,account_settings.keyword_favorite
        //     FROM accounts
        //     INNER JOIN account_settings
        //         ON accounts.id = account_settings.account_id
        //     INNER JOIN operation_statuses
        //         ON accounts.id = operation_statuses.account_id
        //         AND operation_statuses.is_favorite = 1
        //         -- AND operation_statuses.is_flozen = 0
        //         -- AND operation_statuses.stopped_at <  SUBTIME(NOW(),\'00:15:00\')
        //         '
        // );
    }

    public function execute()
    {
        logger('execute1');
        // foreach ($this->accounts as  $account) {
        //     // Twitterアカウントのインスタンス作成
        //     $twitterAccount = new TwitterAccount($account->access_token);
        //     // いいねキーワードのリスト作成
        //     $keywords = empty($account->keyword_favorite) ? [] : explode(',', $account->keyword_favorite);
        //     try {
        //         foreach ($keywords as $keyword) {
        //             // つぶやきを検索
        //             $tweets = $twitterAccount->searchTweets($keyword)['statuses'];

        //             foreach ($tweets as $tweet) {
        //                 // いいね実行
        //                 $result = $twitterAccount->favoriteTweet($tweet->id_str);
        //             }
        //         }
        //     } catch (TwitterRestrictionException $e) {
        //         // API制限
        //         // 処理を次のアカウントへ
        //         // 前回停止時間を更新
                
        //     } catch (TwitterFlozenException $e){
        //         // 凍結
        //         // 処理を次のアカウントへ
        //         // 稼働フラグを0へ変更
        //         // 凍結フラグを1へ変更
        //     } catch (Exception $e){
        //         // その他例外
        //     }
        // }
    }
}
