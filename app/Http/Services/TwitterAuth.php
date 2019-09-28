<?php

namespace App\Http\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Request;

// Twitterアカウントのアプリ認証を行うためのクラス
class TwitterAuth
{
    /**
     * @return string TwitterAPIのAPI key
     */
    private static function getApiKey()
    {
        return env('TWITTER_API_KEY');
    }
    /**
     * @return string TwitterAPIのAPI secret key
     */
    private static function getApiSecretKey()
    {
        return env('TWITTER_API_SECRET_KEY');
    }
    /**
     * @return string 認証ページのCallback URL
     */
    private static function getCallbackUrl()
    {
        return 'http://127.0.0.1:8000/account/callback';
    }

    /**
     * 認証ページのURLを返す
     * @return string 認証ページURL
     */
    public static function getAuthorizeUrl()
    {
        // myTodo:リクエストトークン取得失敗の例外処理
        // myTodo:認証済みなら、認証画面にアクセスさせない？(アクセストークンが再発行されるかも？)

        $twitter = new TwitterOAuth(self::getApiKey(), self::getApiSecretKey());
        //リクエストトークン取得
        $callbackUrl = 'http://127.0.0.1:8001/account/callback';
        $request_token = $twitter->oauth('oauth/request_token', array('oauth_callback' => $callbackUrl));
        // $request_token = $twitter->oauth('oauth/request_token', array('oauth_callback' => self::getCallbackUrl()));
            
        
        //認証用URL取得
        $url = $twitter->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

        return $url;
    }

    /**
     * 認証したユーザーのアクセストークンを返す。
     * 認証ページからコールバックがあった場合にのみ使う。
     * @return array 認証したユーザーのアクセストークン
     */
    public static function getAccessToken()
    {
        // myTodo:ユーザーが認証をキャンセルした場合（＝deniedという値がクエリ文字列に入る）の処理

        if (!empty(Request::get('oauth_token')) && !empty(Request::get('oauth_verifier'))) {
            // 「連携アプリを認証」をクリックして帰ってきた時

            $oauth_token = Request::get('oauth_token');
            $oauth_verifier = Request::get('oauth_verifier');
            //インスタンス生成
            $twitter = new TwitterOAuth(
                static::getApiKey(),
                static::getApiSecretKey(),
                $oauth_token,
                $oauth_verifier
            );

            //アクセストークン取得
            return $twitter->oauth('oauth/access_token', array('oauth_token' => $oauth_token, 'oauth_verifier' => $oauth_verifier));
        } elseif (!empty(Request::get("denied"))) {
            // 「キャンセル」をクリックして帰ってきた時
            exit ;
        }
    }
}
