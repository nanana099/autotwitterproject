<?php
namespace App\Services;

use App\Account;
use App\AccountSetting;
use Illuminate\Support\Facades\DB;
use \Exception;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\OperationStatus;

class FavoriteExecutor implements ITwitterFunctionExecutor
{
    private $accounts = [];
    public function prepare()
    {
        // 対象リストの作成
        // 前回停止日時、凍結中は除く
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
    }

    public function execute()
    {
        foreach ($this->accounts as  $account) {
            // Twitterアカウントのインスタンス作成
            $twitterAccount = new TwitterAccount($account->access_token);
            // いいねキーワードのリスト作成
            $keywords = empty($account->keyword_favorite) ? [] : explode(',', $account->keyword_favorite);
            try {
                foreach ($keywords as $keyword) {
                    // つぶやきを検索
                    $tweets = $twitterAccount->searchTweets($keyword)['statuses'];

                    foreach ($tweets as $tweet) {
                        // いいね実行
                        $result = $twitterAccount->favorite($tweet->id_str);
                    }
                }
            } catch (TwitterRestrictionException $e) {
                // API制限
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'favorite_stopped_at' => date('Y/m/d H:i:s')))->save();
                // メール送信
            } catch (TwitterFlozenException $e) {
                // 凍結
                OperationStatus::where('account_id', $account->id)->first()->fill(array(
                    'is_favorite' => 0,
                    'is_flozen'=>1,
                    'favorite_stopped_at' => date('Y/m/d H:i:s')))->save();
                // メール送信
            } catch (Exception $e) {
                // その他例外
            }
        }
    }
}
