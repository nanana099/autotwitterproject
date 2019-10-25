<?php
namespace App\Services;

// use App\Account;
// use App\AccountSetting;
use Illuminate\Support\Facades\DB;
use \Exception;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\ReservedTweet;
use App\Account;

class TweetExecutor implements ITwitterFunctionExecutor
{
    private $tweets = [];
    public function prepare()
    {
        // 対象リストの作成
        $this->tweets = DB::select(
            'SELECT 
                r.id ,
                r.content,
                r.submit_date,
                r.account_id,
                a.access_token
            FROM reserved_tweets r
                INNER JOIN operation_statuses o
                ON r.account_id = o.account_id
                AND o.is_flozen = 0
                INNER JOIN accounts a
                ON r.account_id = a.id
            WHERE r.submit_date <= NOW()
            ORDER BY r.account_id
            '
        );
    }

    // TwitterAPIを用いて、つぶやきを投稿する
    private function postTweet(TwitterAccount $twitterAccount, $tweet)
    {
        $twitterAccount->postTweet($tweet->content);
    }

    // DBからツイートの予約情報を削除する
    private function deleteTweetOnDB(int $tweet_id)
    {
        ReservedTweet::find($tweet_id)->delete();
    }

    public function execute()
    {
        $prevAccountId = '';
        $skipAccountId = '';
        $twitterAccount = '';

        // １ツイートごとに繰り返し
        foreach ($this->tweets as $tweet) {
            // API制限または凍結を受けたアカウントは処理を行わない
            if ($skipAccountId === $tweet->account_id) {
                continue;
            }

            if ($prevAccountId !== $tweet->account_id) {
                $prevAccountId = $tweet->account_id;
                $twitterAccount = new TwitterAccount($tweet->access_token);
            }

            try {
                $this->postTweet($twitterAccount, $tweet);
                $this->deleteTweetOnDB($tweet->id);
            } catch (TwitterRestrictionException $e) {
                // API制限のエラー
                $skipAccountId = $tweet->account_id;
                // 前回停止時間を更新
            } catch (TwitterFlozenException $e) {
                // 凍結のエラー
                $skipAccountId = $tweet->account_id;
                // 稼働フラグを0へ変更
                // 凍結フラグを1へ変更
            } catch (Exception $e) {
                // その他例外
                logger($e);
            }
        }
    }
}
