<?php

namespace App\Services;

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
        return config('app.twitter_api_key');
    }
    /**
     * @return string TwitterAPIのAPI secret key
     */
    private static function getApiSecretKey()
    {
        return config('app.twitter_api_secret_key');
    }
    /**
     * @return string 認証ページのCallback URL
     */
    private static function getCallbackUrl()
    {
        return config('app.twitter_oauth_callback_url');
    }

    /**
     * 認証ページのURLを返す
     * @return string 認証ページURL
     */
    public static function getAuthorizeUrl()
    {
        $twitter = new TwitterOAuth(self::getApiKey(), self::getApiSecretKey());
        //リクエストトークン取得
        $callbackUrl = self::getCallbackUrl();
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
            return false;
        }
    }
}
