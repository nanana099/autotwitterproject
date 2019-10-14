<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Services\TwitterAPIErrorChecker;

// Twitterアカウントのオブジェクト
class TwitterAccount
{
    /** @var int Twitterアカウントのuser_id */
    private $user_id;
    /** @var int Twitterアカウントのscreen_name */
    private $screen_name;
    /** @var TwitterOAuth */
    private $twitter;

    public function __construct(string $access_token)
    {
        $access_token_ary = json_decode($access_token, true);

        $this->user_id = $access_token_ary['user_id'];
        $this->screen_name = $access_token_ary['screen_name'];
        $this->twitter = new TwitterOAuth(
            env('TWITTER_API_KEY'),
            env('TWITTER_API_SECRET_KEY'),
            $access_token_ary['oauth_token'],
            $access_token_ary['oauth_token_secret']
        );
    }

    public function getScreenName()
    {
        return $this->screen_name;
    }

    /**
     * つぶやきを投稿する
     * @param string $msg
     */
    public function tweet(string $msg)
    {
        // myTodo:画像やURLも呟けるようにする
        $userInfo = get_object_vars($this->twitter->post(
            "statuses/update",
            array(
                'status' => $msg,
            )
        ));
    }
    public function removeFollower(int $user_id)
    {
    }
    public function followUser(int $user_id)
    {
    }
    public function favoriteTweet(string $id)
    {
        $result = get_object_vars($this->twitter->post(
            "favorites/create",
            array(
                'id' => $id,
                'include_entities' => false
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }
    public function searchTweets(string $word)
    {
        $result = get_object_vars($this->twitter->get(
            "search/tweets",
            array(
                'q' => $word,
                'lang' => 'ja',
                'locale' => 'ja',
                'result_type' => 'recent', // 最近のツイートを検索結果として取得
                'count' => 1, // 最大取得件数
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }
    public function existsAccount(string $screen_name)
    {
    }
    public function getMyAccountInfo()
    {
    }
    public function getAccountInfo(string $screen_name)
    {
        // users/lookup
    }
    public function getFollowerList(string $user_id)
    {
    }
    public function getTweetLatest(string $screen_name)
    {
    }
}
