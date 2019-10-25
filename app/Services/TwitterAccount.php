<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\TwitterapiCalledLog;
use App\Services\TwitterAPIErrorChecker;
use App\Exceptions\TwitterRestrictionException;

// Twitterアカウントのオブジェクト
class TwitterAccount
{
    // Twitterアカウントのuser_id(Twitter内で一意で変わらないID)
    private $user_id;
    // Twitterアカウントのscreen_name(Twitter内で一意ではない、ユーザーが変更可能なID)
    private $screen_name;
    // TwitterAuth
    private $twitter;
    // TwitterAPIのリソースの使用状況
    private $apiLimit ;
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

    // TwitterAPIの使用回数制限を判定する
    private function checkLimit(string $resourceName)
    {
        // 一部のTwitterAPIのリソースは、利用状況がTwitterAPIで確認できない。システムで管理している情報に照らしあわせてチェックする
        if (in_array($resourceName, $this::MANAGE_LIMIT_RESOURCE)) {
            if ($resourceName === self::FRIENDSHIPS_CREATE) {
                // １５分制限
                if ($this->calledCountFriendshipsCreateBefore15Minute + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_15MINUTE) {
                    logger()->debug('フレンドシップ制限：firndships/createの１５分制限'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                // ２４時間制限
                if ($this->calledCountFriendshipsCreateBefore24Hour + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR) {
                    logger()->debug('フレンドシップ制限：firndships/createの２４時間制限'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    logger()->debug('フレンドシップ制限：フレンドシップ全体（１５分）'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsCreateNow++;
            }

            if ($resourceName === self::FRIENDSHIPS_DESTROY) {
                // ２４時間制限
                if ($this->calledCountFriendshipsDestroyBefore24Hour + $this->calledCountFriendshipsDestroyNow >= $this::FRIENDHSIPS_DESTROY_LIMIT_PER_24HOUR) {
                    logger()->debug('フレンドシップ制限：firndships/destroyの２４時間制限'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    logger()->debug('フレンドシップ制限：フレンドシップ全体（１５分）'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsDestroyNow++;
            }

            if ($resourceName === self::FRIENDSHIPS_LOOKUP) {
                // １５分制限（フレンドシップ全体）
                if ($this->getAmountCountCalledFriendshipsBefore15Minute() >= $this::FRIENDSHIPS_LIMIT_PER_15MINUTE) {
                    logger()->debug('フレンドシップ制限：フレンドシップ全体（１５分）'." ".$this->user_id);
                    throw new TwitterRestrictionException();
                }
                $this->calledCountFriendshipsLookupNow++;
            }
        }
        
        // TwitterAPIのリソースの利用状況を、利用状況確認用のTwitterAPIにて確認
        if (!isset($this->apiLimit)) {
            $this->apiLimit = json_decode(json_encode($this->getRateLimit()['resources']), true);
        }
        $resource_parent = explode('/', $resourceName)[0];
        $resource_child = '/'.$resourceName;
        if (!empty($this->apiLimit[$resource_parent][$resource_child])) {
            if ($this->apiLimit[$resource_parent][$resource_child]['remaining'] > 0) {
                // １回使用
                $this->apiLimit[$resource_parent][$resource_child]['remaining'] -= 1;
            } else {
                logger()->debug('リソース回数制限'.' '.$resourceName." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
        }
    }

    // つぶやきを投稿する
    public function postTweet(string $msg)
    {
        $resourceName = "statuses/update";
        $this->log($resourceName, $msg);

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
        $this->log($resourceName, $user_id);

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
        $this->log($resourceName, $user_id);

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
        $this->log($resourceName, $cursor);

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
        $this->log($resourceName);

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

    // 指定のツイートを「いいね」をする
    public function favorite(string $id)
    {
        $resourceName = "favorites/create";
        $this->log($resourceName, $id);

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->post(
            $resourceName,
            array(
                'id' => $id,
                'include_entities' => false
            )
        ));
        if (!empty($result['errors'])) {
            $errorCode = $result['errors'][0]->code;
            // すでにいいねしているツイートをいいねすると、139が返る
            if ($errorCode === 139) {
                return array();
            }
            // いいねしようとしたアカウントが不在の場合に発生
            if ($errorCode === 144) {
                return array();
            }
        }

        // エラーチェック
        TwitterAPIErrorChecker::check($result);


        return $result;
    }

    // 指定のキーワードでツイートを検索する
    public function searchTweets(string $word)
    {
        $resourceName = "search/tweets";
        $this->log($resourceName, $word);

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'q' => $word,
                'lang' => 'ja',
                'locale' => 'ja',
                'result_type' => 'recent', // 最近のツイートを検索結果として取得
                'count' => 15, // 取得件数
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
        $this->log($resourceName, $user_id);

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
        $this->log($resourceName);

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
        $this->log($resourceName, $user_ids);

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
        $this->log($resourceName, $screen_name, $cursor);

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
        $resourceName = "application/rate_limit_status";
        $this->log($resourceName);

        $result = get_object_vars($this->twitter->get(
            $resourceName
        ));
        // エラーチェック
        TwitterAPIErrorChecker::check($result);

        return $result;
    }

    // 一部リソースの利用状況計算用
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

    // 一部リソースの利用状況を示す変数を初期化
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

    // 一部リソースの利用状況をDBに保存
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

    // 一部リソースの利用状況をDBから取得、フィールド変数に格納
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

    // 一部リソースの利用状況をDBから取得、フィールド変数に格納
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

    // ログ出力用
    private function log(string $resourceName, ...$args)
    {
        try {
            $str = "TwitterAPI呼び出し：".$resourceName." ".$this->user_id." ";
            $str .= implode(" ", $args);
            logger($str);
        } catch (Exception $e) {
            logger('TwitterAPIロギングで例外');
            logger()->info($e);
        }
    }
}
