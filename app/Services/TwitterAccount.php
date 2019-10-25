<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Services\TwitterAPIErrorChecker;
use App\Exceptions\TwitterRestrictionException;
use App\TwitterapiCalledLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
    // 一部TwitterAPIのリソースの使用状況管理用の定数と変数
    private const FRIENDSHIPS_CREATE    = 'friendships/create';
    private const FRIENDSHIPS_DESTROY   = 'friendships/destroy';
    private const FRIENDSHIPS_LOOKUP    = 'friendships/lookup';
    private const MANAGE_LIMIT_RESOURCE =[self::FRIENDSHIPS_CREATE,self::FRIENDSHIPS_DESTROY,self::FRIENDSHIPS_LOOKUP]; // 自前で呼び出し回数を制限したいリソース名
    private const FRIENDSHIPS_CREATE_LIMIT_PER_15MINUTE = 15;   // friendships/createの15分上限
    private const FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR = 1000;   // friendships/createの２４時間上限
    private const FRIENDHSIPS_DESTROY_LIMIT_PER_24HOUR = 1000;  // friendships/destroyの２４時間上限
    private const FRIENDSHIPS_LIMIT_PER_15MINUTE = 170;         // friendshipsの１５分上限
    private $calledCountFriendshipsCreateBefore15Minute;
    private $calledCountFriendshipsCreateBefore24Hour;
    private $calledCountFriendshipsDestroyBefore15Minute;
    private $calledCountFriendshipsDestroyBefore24Hour;
    private $calledCountFriendshipsLookupBefore15Minute;
    // private $calledCountFriendshipsLookupBefore24Hour; friendships/lookupには２４時間上限は設けないので不要
    private $calledCountFriendshipsCreateNow;
    private $calledCountFriendshipsDestroyNow;
    private $calledCountFriendshipsLookupNow;

    // コンストラクタ
    public function __construct(string $access_token)
    {
        // Twitterアカウント情報の設定
        $access_token_ary = json_decode($access_token, true);
        $this->user_id = $access_token_ary['user_id'];
        $this->screen_name = $access_token_ary['screen_name'];
        $this->twitter = new TwitterOAuth(
            env('TWITTER_API_KEY'),
            env('TWITTER_API_SECRET_KEY'),
            $access_token_ary['oauth_token'],
            $access_token_ary['oauth_token_secret']
        );

        // TwitterAPIの過去の利用状況を取得し、メンバ変数に格納
        $this->getCalledResourceHistory();
    }

    // デストラクタ
    public function __destruct()
    {
        // TwitterAPIの利用状況をDBに保存
        $this->postCalledResourceHistory();
    }

    // TwitterAPIの関数の使用回数制限の判定
    private function checkLimit(string $resourceName)
    {
        // 一部のTwitterAPIのリソースは、利用状況がTwitterAPIで確認できないので、自分で管理している情報に照らしあわせてチェックする
        if (in_array($resourceName, $this::MANAGE_LIMIT_RESOURCE)) {
            if ($resourceName === self::FRIENDSHIPS_CREATE) {
                // １５分制限
                if ($this->calledCountFriendshipsCreateBefore15Minute + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_15MINUTE) {
                    throw new TwitterRestrictionException();
                }
                // ２４時間制限
                if ($this->calledCountFriendshipsCreateBefore24Hour + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR) {
                    throw new TwitterRestrictionException();
                }
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsCreateNow++;
            }

            if ($resourceName === self::FRIENDSHIPS_DESTROY) {
                // ２４時間制限
                if ($this->calledCountFriendshipsDestroyBefore24Hour + $this->calledCountFriendshipsDestroyNow >= $this::FRIENDHSIPS_DESTROY_LIMIT_PER_24HOUR) {
                    throw new TwitterRestrictionException();
                }
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsDestroyNow++;
            }

            if ($resourceName === self::FRIENDSHIPS_LOOKUP) {
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsLookupNow++;
            }
        }
        
        // TwitterAPIのリソースの利用状況を、利用状況確認用のTwitterAPIにて確認
        if (!isset($this->apilimit)) {
            $this->apiLimit = json_decode(json_encode($this->getRateLimit()['resources']), true);
        }
        $resource_parent = explode('/', $resourceName)[0];
        $resource_child = '/'.$resourceName;
        if (!empty($this->apiLimit[$resource_parent][$resource_child])) {
            if ($this->apiLimit[$resource_parent][$resource_child]['remaining'] > 0) {
                $this->apiLimit[$resource_parent][$resource_child]['remaining'] -= 1;
            } else {
                throw new TwitterRestrictionException();
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

        $this->checkLimit($resourceName);
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

    // 指定のアカウントのフォローを外す
    public function unfollow(string $user_id)
    {
        $resourceName = "friendships/destroy";

        $this->checkLimit($resourceName);
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

    // ユーザーをフォローする
    public function follow(string $user_id)
    {
        $resourceName =  "friendships/create";

        $this->checkLimit($resourceName);
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

    // 自アカウントのフォロワー数を取得する
    public function getMyFollowedList(string $cursor)
    {
        $resourceName = "friends/ids";

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'user_id' => $this->user_id,
                'stringify_ids' => true,
                'count' => 5000,
                'cursor' => $cursor
            )
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);
        return $result;
    }

    // 自アカウントのフォロワー数を取得する
    public function getMyFollowersCount()
    {
        $resourceName =   "users/show";

        $this->checkLimit($resourceName);
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

    // 指定のツイートに対して「いいね」をする
    public function favorite(string $id)
    {
        $resourceName = "favorites/create";

        $this->checkLimit($resourceName);
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

    // 指定のキーワードでツイートを検索する
    public function searchTweets(string $word)
    {
        $resourceName = "search/tweets";

        $this->checkLimit($resourceName);
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

    // 指定のアカウントの最新ツイートを取得する
    public function getLatestTweet(string $user_id)
    {
        $resourceName = "statuses/user_timeline";

        $this->checkLimit($resourceName);
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

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'user_id' => $this->user_id,
            )
        ));
        return $result;
    }

    // 自アカウントと指定のアカウントの関係の情報を取得する
    public function getFriendShips(string $user_ids)
    {
        $resourceName = "friendships/lookup";

        $this->checkLimit($resourceName);
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

    // 指定のアカウントのフォロワーの情報を取得する
    public function getFollowerList(string $screen_name, $cursor)
    {
        $resourceName = "followers/list";
        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'screen_name' => $screen_name,
                'count' => 200, // 取得件数
                'status' => false,
                'include_user_entities' => false,
                'cursor' => $cursor
            )
        ));

        // 存在しないアカウントを$screen_nameに指定するとTwitterAPIはエラーになる。しかしシステム上は正常扱いにするため、ここでチェック
        if (!empty($result['errors'])) {
            $errorCode = $result['errors'][0]->code;
            if ($errorCode === 34) {
                return array();
            }
        }

        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
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


    private function getAmountCountCalledFriendshipsBefore15Minute()
    {
        return
        $this->calledCountFriendshipsCreateBefore15Minute   +
        $this->calledCountFriendshipsDestroyBefore15Minute  +
        $this->calledCountFriendshipsLookupBefore15Minute   +
        $this->calledCountFriendshipsCreateNow              +
        $this->calledCountFriendshipsDestroyNow             +
        $this->calledCountFriendshipsLookupNow ;
    }

    private function getCalledResourceHistory()
    {
        $this->calledCountFriendshipsCreateBefore15Minute   = 0;
        $this->calledCountFriendshipsCreateBefore24Hour     = 0;
        $this->calledCountFriendshipsDestroyBefore15Minute  = 0;
        $this->calledCountFriendshipsDestroyBefore24Hour    = 0;
        $this->calledCountFriendshipsLookupBefore15Minute   = 0;
        $this->calledCountFriendshipsCreateNow              = 0;
        $this->calledCountFriendshipsDestroyNow             = 0;
        $this->calledCountFriendshipsLookupNow              = 0;
        $this->setCalledResourceSountBefore15Minute();
        $this->setCalledResourceSountBefore24Hour();
    }

    private function postCalledResourceHistory()
    {
        if ($this->calledCountFriendshipsCreateNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FRIENDSHIPS_CREATE, 'count' => $this->calledCountFriendshipsCreateNow]))->save();
        }
        if ($this->calledCountFriendshipsDestroyNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FRIENDSHIPS_DESTROY, 'count' => $this->calledCountFriendshipsDestroyNow]))->save();
        }
        if ($this->calledCountFriendshipsLookupNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FRIENDSHIPS_LOOKUP, 'count' => $this->calledCountFriendshipsLookupNow]))->save();
        }
    }

    private function setCalledResourceSountBefore15Minute()
    {
        $now = new Carbon();
        // 過去１５分間の呼び出し履歴
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('user_id', '=', $this->user_id)
        ->where('created_at', '>', $now->subMinute(15))
        ->groupBy('resource_name')
        ->get();

        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::FRIENDSHIPS_CREATE:
                    $this->calledCountFriendshipsCreateBefore15Minute = $val->count;
                    break;
                case self::FRIENDSHIPS_DESTROY:
                    $this->calledCountFriendshipsDestroyBefore15Minute = $val->count;
                    break;
                case self::FRIENDSHIPS_LOOKUP:
                    $this->calledCountFriendshipsLookupBefore15Minute = $val->count;
                    break;
            }
        }
    }

    private function setCalledResourceSountBefore24Hour()
    {
        $now = new Carbon();
        
        // 過去２４時間の呼出履歴
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('user_id', '=', $this->user_id)
        ->where('created_at', '>', $now->subHour(24))
        ->groupBy('resource_name')
        ->get();
        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::FRIENDSHIPS_CREATE:
                    $this->calledCountFriendshipsCreateBefore24Hour = $val->count;
                    break;
                case self::FRIENDSHIPS_DESTROY:
                    $this->calledCountFriendshipsDestroyBefore24Hour = $val->count;
                    break;
                case self::FRIENDSHIPS_LOOKUP:
                    // 不要
                    break;
            }
        }
    }
}
