<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Facades\DB;
use App\ReservedTweet;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterRestrictionException;

// 自動ツイート実行クラス
class TweetExecutor implements ITwitterFunctionExecutor
{
    // 投稿するツイート
    private $tweets = [];

    // 準備
    public function prepare()
    {
        logger()->info('TweetExecutor：prepare-start');

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

        logger()->info('TweetExecutor：prepare-end'.' 対象件数：'.count($this->tweets));
    }

    // DBからツイートの予約情報を削除する
    private function deleteTweetOnDB(int $tweet_id)
    {
        ReservedTweet::find($tweet_id)->delete();
    }

    public function execute()
    {
        logger()->info('TweetExecutor：execute-start');
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
                // Todo:DBに停止日時格納すべき？
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
                logger()->error($e);
            }
        }
        logger()->info('TweetExecutor：execute-end');
    }

    // TwitterAPIを用いて、つぶやきを投稿する
    private function postTweet(TwitterAccount $twitterAccount, $tweet)
    {
        $twitterAccount->postTweet($tweet->content);
    }
}
