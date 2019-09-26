<?php

namespace App\Http\Services;

use Abraham\TwitterOAuth\TwitterOAuth;

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
        $access_token_ary = json_decode($access_token);

        $this->user_id = $access_token_ary['user_id'];
        $this->screen_name = $access_token_ary['screen_name'];
        $this->twitter = new TwitterOAuth(
            env('TWITTER_API_KEY'),
            env('TWITTER_API_SECRET_KEY'),
            $access_token_ary['oauth_token'],
            $access_token_ary['oauth_token_secret']
        );
    }

    /**
     * つぶやきを投稿する
     * @param string $msg
     */
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
    public function favoriteTweet(int $id)
    {
    }
    public function searchTweets(string $word)
    {
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
