<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Facades\DB;
use App\Account;
use App\ReservedTweet;
use App\OperationStatus;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterAuthExipiredException;

// 自動ツイート実行クラス
class TweetExecutor implements ITwitterFunctionExecutor
{
    // 投稿するツイート
    private $tweets = [];

    // 準備
    public function prepare()
    {
        MailSender::send('$user->name', '$twitterAccount->getScreenName()', 'nananabaito@yahoo.co.jp', MailSender::EMAIL_TWEET_COMPLATED);

        logger()->info('TweetExecutor：prepare-start');


        // API制限を受けた後に再度リクエストを送るのに開ける時間
        // 15分の理由：TwitterAPIのコール回数が15分枠で区切られているため
        $whenRestrictedInterval = '00:15:00';


        // 対象リストの作成
        $this->tweets = DB::select(
            "SELECT 
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
                AND o.is_flozen = 0
                AND o.tweet_stopped_at <  SUBTIME(NOW(),'{$whenRestrictedInterval}')
            ORDER BY r.account_id
            "
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
            try {

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

                    $accountFromDB = Account::find($tweet->account_id);
                    // アカウントを所持するユーザー
                    $user = $accountFromDB->user()->get()[0];
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_TWEET_COMPLATED);
                } catch (TwitterRestrictionException $e) {
                    // API制限のエラー
                    $skipAccountId = $tweet->account_id;
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    OperationStatus::where('account_id', $tweet->account_id)->first()->fill(array(
                    'tweet_stopped_at' => date('Y/m/d H:i:s')))->save();
                } catch (TwitterFlozenException $e) {
                    // 凍結のエラー
                    $skipAccountId = $tweet->account_id;
                    // 次回起動に時間をあけるため、制限がかかった時刻をDBに記録
                    // 凍結時は、自動機能を停止する。ユーザーに凍結解除と再稼働をメールで依頼。
                    OperationStatus::where('account_id', $tweet->account_id)->first()->fill(array(
                'is_follow' => 0,
                'is_unfollow' => 0,
                'is_favorite' => 0,
                'is_flozen'=>1,
                'tweet_stopped_at' => date('Y/m/d H:i:s')))->save();

                    $accountFromDB = Account::find($tweet->account_id);
                    // アカウントを所持するユーザー
                    $user = $accountFromDB->user()->get()[0];
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::EMAIL_FLOZEN);
                }catch (TwitterAuthExipiredException $e) {
                    $skipAccountId = $tweet->account_id;
                    OperationStatus::where('account_id', $tweet->account_id)->first()->fill(array(
                'is_follow' => 0,
                'is_unfollow' => 0,
                'is_favorite' => 0,
                'is_flozen'=>1,
                'tweet_stopped_at' => date('Y/m/d H:i:s')))->save();

                    $accountFromDB = Account::find($tweet->account_id);
                    // アカウントを所持するユーザー
                    $user = $accountFromDB->user()->get()[0];
                    MailSender::send($user->name, $twitterAccount->getScreenName(), $user->email, MailSender::AUTH_EXIPIRED);
                }
            } catch (Exception $e) {
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
