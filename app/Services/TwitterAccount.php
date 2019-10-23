<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Services\TwitterAPIErrorChecker;
use \Exception;

// Twitterアカウントのオブジェクト
class TwitterAccount
{
    private $user_id;
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
    public function unfollow(string $user_id)
    {
        $result =  get_object_vars(
            $this->twitter->post(
                "friendships/destroy",
                array(
                'user_id' => $user_id,
            )
            )
        );
        TwitterAPIErrorChecker::check($result);
        return $result;
    }

    // ユーザーをフォローする
    public function follow(int $user_id)
    {
        $result = get_object_vars($this->twitter->post(
            "friendships/create",
            array(
                'user_id' => $user_id,
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }

    public function getMyFollowersCount()
    {
        $result = get_object_vars($this->twitter->get(
            "users/show",
            array(
                'user_id' => $this->user_id
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result['followers_count'];
    }

    // いいね実行
    public function favorite(string $id)
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
     
    // ツイート検索
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
    // 最新ツイートを取得する
    public function getLatestTweet(string $user_id)
    {
        $result = ($this->twitter->get(
            "statuses/user_timeline",
            array(
                    'user_id' => $user_id,
                    'result_type' => 'recent', // 最近のツイートを検索結果として取得
                    'count' => 1, // 最大取得件数
                    'execlude_replies' => false, // リプライでも取得する
                    'include_rts' => true , // リツイートでも取得する
                )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }

    // 最新ツイートの作成日時を取得する
    public function getLatestTweetDate(string $user_id)
    {
        $tweet = $this->getLatestTweet($user_id);
        if ($tweet[0]) {
            return $tweet[0]->created_at;
        } else {
            // つぶやきが０件数の場合
            return false;
        }
    }

    public function isFollowedBy($user_id)
    {
    }
    public function existsAccount(string $screen_name)
    {  
    } 
    public function getMyAccountInfo()
    {
        $result = get_object_vars($this->twitter->get(
            "users/show",
            array(
                'user_id' => $this->user_id,
            )
        ));
        return $result;

    }
    public function getAccountInfo(string $screen_name)
    {
        // users/lookup
    }

    // 自分からみた他ユーザーとの関係
    public function getFriendShips(string $user_ids)
    {
        $result = $this->twitter->get(
            "friendships/lookup",
            array(
                    'user_id' => $user_ids,
                )
        );
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }

    // フォロワーの情報を取得する
    public function getFollowerList(string $screen_name)
    {
        $result = get_object_vars($this->twitter->get(
            "followers/list",
            array(
                'screen_name' => $screen_name,
                'count' => 20, // 最大取得件数
                'status' => false,
                'include_user_entities' => false
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }
  

    // アカウントのTwitterAPI制限を調べる
    public function getRateLimit()
    {
        $result = get_object_vars($this->twitter->get(
            "application/rate_limit_status",
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }
}
