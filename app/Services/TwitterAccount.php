<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Services\TwitterAPIErrorChecker;
use App\Exceptions\TwitterRestrictionException;

// Twitterアカウントのオブジェクト
class TwitterAccount
{
    // Twitterアカウントのuser_id(Twitter内で一意で変わらないID)
    private $user_id;
    // Twitterアカウントのscreen_name(Twitter内で一意ではない、ユーザーが変更可能なID)
    private $screen_name;
    /** @var TwitterOAuth */
    private $twitter;
    // TwitterAPIのリソースの使用状況
    private $apiLimit;
    //
    private const FOLLOW_LIMIT_PER_15MINUTE = 15;
    private const FOLLOW_LIMIT_PER_DAY = 1000;
    private const UNFOLLOW_LIMIT_PER_DAY = 1000;
    private const FRIENDSHIPS_LIMIT_PER_15MINUTE = 170;


    // コンストラクタ
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

    // TwitterAPIの関数の使用回数制限の判定
    private function checkLimit(string $resourceName)
    {
        if (!isset($this->apilimit)) {
            $this->apiLimit = json_decode(json_encode($this->getRateLimit()['resources']), true);
        }

        $resource_parent = explode('/', $resourceName)[0];
        $resource_child = '/'.$resourceName;

        if (empty($this->apiLimit[$resource_parent][$resource_child])) {
            return true;
        } else {
            if ($this->apiLimit[$resource_parent][$resource_child]['remaining'] > 0) {
                $this->apiLimit[$resource_parent][$resource_child]['remaining'] -= 1;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * つぶやきを投稿する
     * @param string $msg
     */
    public function postTweet(string $msg)
    {
        $resourceName = "statuses/update";

        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->post(
                $resourceName,
                array(
                    'status' => $msg,
                )
            ));
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
    
            return $result;
        }
    }

    // 指定のアカウントのフォローを外す
    public function unfollow(string $user_id)
    {
        $resourceName = "friendships/destroy";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result =  get_object_vars(
                $this->twitter->post(
                    $resourceName,
                    array(
                        'user_id' => $user_id,
                    )
                )
            );
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
            return $result;
        }
    }

    // ユーザーをフォローする
    public function follow(int $user_id)
    {
        $resourceName =  "friendships/create";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->post(
                $resourceName,
                array(
                    'user_id' => $user_id,
                )
            ));
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
            return $result;
        }
    }

    // 自アカウントのフォロワー数を取得する
    public function getMyFollowersCount()
    {
        $resourceName =   "users/show";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->get(
                $resourceName,
                array(
                    'user_id' => $this->user_id
                )
            ));
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
            return $result['followers_count'];
        }
    }

    // 指定のツイートに対して「いいね」をする
    public function favorite(string $id)
    {
        $resourceName = "favorites/create";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->post(
                $resourceName,
                array(
                    'id' => $id,
                    'include_entities' => false
                )
            ));
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
    
            return $result;
        }
    }
     
    // 指定のキーワードでツイートを検索する
    public function searchTweets(string $word)
    {
        $resourceName = "search/tweets";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->get(
                $resourceName,
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
    }

    // 指定のアカウントの最新ツイートを取得する
    public function getLatestTweet(string $user_id)
    {
        $resourceName = "statuses/user_timeline";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = ($this->twitter->get(
                $resourceName,
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
    }

    // 指定のアカウントの最新ツイートの作成日時を取得する
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
    // 自アカウントの情報を取得する
    public function getMyAccountInfo()
    {
        $resourceName = "users/show";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->get(
                $resourceName,
                array(
                    'user_id' => $this->user_id,
                )
            ));
            return $result;
        }
    }

    // 自アカウントと指定のアカウントの関係の情報を取得する
    public function getFriendShips(string $user_ids)
    {
        $resourceName = "friendships/lookup";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = $this->twitter->get(
                $resourceName,
                array(
                    'user_id' => $user_ids,
                )
            );
            // エラーチェック
            TwitterAPIErrorChecker::check($result);
    
            return $result;
        }
    }

    // 指定のアカウントのフォロワーの情報を取得する
    public function getFollowerList(string $screen_name)
    {
        $resourceName = "followers/list";
        
        if (!$this->checkLimit($resourceName)) {
            throw new TwitterRestrictionException();
        } else {
            $result = get_object_vars($this->twitter->get(
                $resourceName,
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
    }
  
    // 自アカウントのTwitterAPI制限を調べる
    private function getRateLimit()
    {
        $result = get_object_vars($this->twitter->get(
            "application/rate_limit_status",
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }
}
