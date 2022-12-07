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
    // TwitterAuth
    private $twitter;
    // TwitterAPIのリソースの使用状況
    private $apiLimit ;
    // TwitterAPIのリソース使用制限の管理用クラス
    private $twitterRestrictManager;

    // コンストラクタ
    public function __construct(string $access_token)
    {
        // Twitterアカウント情報の設定
        $access_token_ary = json_decode($access_token, true);
        $this->user_id = $access_token_ary['user_id'];
        $this->screen_name = $access_token_ary['screen_name'];
        $this->twitter = new TwitterOAuth(
            config('app.twitter_api_key'),
            config('app.twitter_api_secret_key'),
            $access_token_ary['oauth_token'],
            $access_token_ary['oauth_token_secret']
        );

        $this->twitterRestrictManager = new TwitterRestrictManager($this->user_id);
    }

    // TwitterAPIの使用回数制限を判定する。使用回数制限に達する場合は、TwitterRestriceionExceptionを発生する
    private function checkLimit(string $resourceName)
    {
        // TwitterAPIの利用制限状況確認用のAPI「application/rate_limit_status」で確認できないリソースは、自システムのDBで回数を管理
        $this->twitterRestrictManager->check($resourceName);

        // TwitterAPIのリソースの利用状況を、利用状況確認用のTwitterAPI「application/rate_limit_status」にて確認する
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

    // 自身のscreen_nameを返す
    public function getScreenName()
    {
        return $this->screen_name;
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

        if (!empty($result['errors'])) {
            $errorCode = $result['errors'][0]->code;
            // 鍵アカへ再度リクエストすると発生。無視
            if ($errorCode === 160) {
                return array();
            }
        }

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

    // 自アカウントのフォロー数を取得する
    public function getMyFollowCount()
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
        return $result['friends_count'];
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

        if ($this->twitter->getLastHttpCode() === 429) {
            // favorites/createで回数制限にかかると、HTTPコードは帰ってくるが、ボディ部が空なので、ここで例外を発生させる
            throw new TwitterRestrictionException();
        }

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
    public function searchTweets(string $word, int $count = 25)
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
                'count' => $count, // 取得件数
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
    public function getFollowerList(string $screen_name, $cursor, $num = 200)
    {
        $resourceName = "followers/list";
        $this->log($resourceName, $screen_name, $cursor);

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'screen_name' => $screen_name,
                'count' => $num, // 取得件数 200が最大
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

    // フォロワーのIDを取得
    public function getFollowerIds(string $screen_name, $cursor, $num = 200)
    {
        $resourceName = "followers/ids";
        $this->log($resourceName, $screen_name, $cursor);

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->get(
            $resourceName,
            array(
                'screen_name' => $screen_name,
                'count' => $num, // 取得件数 5000が最大
                'cursor' => $cursor,
                'stringify_ids'  => true
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


    // フォロー中のリストを取得
    public function getFollowedUsers($cursor = -1, $num = 200)
    {
        $resourceName = "friends/ids";
        $this->log($resourceName, $this->user_id);

        $this->checkLimit($resourceName);
        $result =  get_object_vars(
            $this->twitter->get(
                $resourceName,
                array(
                    'user_id' => $this->user_id,
                    'cursor' => $cursor,
                    'count' => $num,
                    'stringify_ids'  => true
                )
            )
        );
        // エラーチェック
        TwitterAPIErrorChecker::check($result);
        return $result;
    }

    // ユーザーをフォローする
    public function mute(string $user_id)
    {
        $resourceName =  "mutes/users/create";
        $this->log($resourceName, $user_id);

        $this->checkLimit($resourceName);
        $result = get_object_vars($this->twitter->post(
            $resourceName,
            array(
                'user_id' => $user_id,
            )
        ));


        if (!empty($result['errors'])) {
            $errorCode = $result['errors'][0]->code;
            // 鍵アカへ再度リクエストすると発生。無視
            if ($errorCode === 160) {
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
