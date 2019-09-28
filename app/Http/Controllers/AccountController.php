<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\TwitterAuth;
use App\Http\Services\TwitterAccount;
use App\User;
use App\Account;
use App\AccountSetting;
use App\ReservedTweet;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function add()
    {
        $max_account = 10;
        if (Auth::user()->accounts()->count() >= $max_account) {
            // １ユーザーが登録できるアカウント数に上限を設ける
        } else {
            $authUrl = TwitterAuth::getAuthorizeUrl();
            return redirect($authUrl);
        }
    }

    public function callback()
    {
        $accessToken = TwitterAuth::getAccessToken();
        $account_id = $accessToken['user_id'];
        $account = Account::find($account_id);

        if (!empty($account) && $account['user_id'] !== Auth::id()) {
            // すでにTwitterアカウントが他のユーザーによって登録済みの場合は不可
            return redirect()->route('mypage.account')->with('flash_message_error', 'Twitterアカウントが他のユーザにより登録済みのため、登録できませんでした。');
        } else {
            $accessTokenStr = json_encode($accessToken);

            // アカウントのアイコン画像のURLが欲しいので、TwitterAPIを呼び出す
            $account = new TwitterAccount($accessTokenStr);
            $accountInfo = $account->getMyAccountInfo();
            logger($accountInfo);
            $screen_name = $accountInfo['screen_name'];
            $image_url = $accountInfo['profile_image_url_https'];
        
            // mytodo: アクセストークン暗号化
            // DBへアカウント情報を格納
            Account::updateOrCreate(['id' => $account_id], ['access_token' => $accessTokenStr,'user_id' => Auth::id(),'screen_name' => $screen_name, 'image_url' => $image_url]);
            AccountSetting::updateOrCreate(['account_id' => $account_id,'target_accounts' => '']);

            return redirect()->route('mypage.account')->with('flash_message_success', 'Twitterアカウントの登録に成功しました。');
        }
    }

    public function destroy(Request $request)
    {
        $account = Auth::user()->accounts()->find($request['id']);
        $resultAry = array('result' => false);
        if (empty($account)) {
            return response()->json($resultAry);
        }
        if ($account->delete()) {
            $resultAry['result'] = true;
            return response()->json($resultAry);
        } else {
            return response()->json($resultAry);
        }
    }

    public function get()
    {
        // $accounts = Auth::user()->accounts()->get();
        // return response()->json($accounts);

        // mytodo:
        // ユーザー情報からアカウント引っ張ってくる（１〜１０）
        // それぞれをTwitterAPI使って、アカウント情報取得
        // レスポンスに情報を載せて返す

        $result = json_decode('[{"id":250131337,"id_str":"250131337","name":"\u5c0f\u6797\u592a\u90ce","screen_name":"daffa666","location":"","profile_location":null,"description":"aaa","url":null,"entities":{"description":{"urls":[]}},"protected":false,"followers_count":1,"friends_count":2,"listed_count":0,"created_at":"Thu Feb 10 13:52:29 +0000 2011","favourites_count":0,"utc_offset":null,"time_zone":null,"geo_enabled":false,"verified":false,"statuses_count":4,"lang":null,"status":{"created_at":"Wed May 18 22:41:56 +0000 2011","id":70982479955238914,"id_str":"70982479955238914","text":"@daffa666","truncated":false,"entities":{"hashtags":[],"symbols":[],"user_mentions":[{"screen_name":"daffa666","name":"\u5c0f\u6797\u592a\u90ce","id":250131337,"id_str":"250131337","indices":[0,9]}],"urls":[]},"source":"\u003ca href=\"http:\/\/twitter.com\" rel=\"nofollow\"\u003eTwitter Web Client\u003c\/a\u003e","in_reply_to_status_id":70980514001068032,"in_reply_to_status_id_str":"70980514001068032","in_reply_to_user_id":250131337,"in_reply_to_user_id_str":"250131337","in_reply_to_screen_name":"daffa666","geo":null,"coordinates":null,"place":null,"contributors":null,"is_quote_status":false,"retweet_count":0,"favorite_count":0,"favorited":false,"retweeted":false,"lang":"und"},"contributors_enabled":false,"is_translator":false,"is_translation_enabled":false,"profile_background_color":"352726","profile_background_image_url":"http:\/\/abs.twimg.com\/images\/themes\/theme5\/bg.gif","profile_background_image_url_https":"https:\/\/abs.twimg.com\/images\/themes\/theme5\/bg.gif","profile_background_tile":false,"profile_image_url":"http:\/\/abs.twimg.com\/sticky\/default_profile_images\/default_profile_normal.png","profile_image_url_https":"https:\/\/abs.twimg.com\/sticky\/default_profile_images\/default_profile_normal.png","profile_link_color":"D02B55","profile_sidebar_border_color":"829D5E","profile_sidebar_fill_color":"99CC33","profile_text_color":"3E4415","profile_use_background_image":true,"has_extended_profile":false,"default_profile":false,"default_profile_image":true,"following":null,"follow_request_sent":null,"notifications":null,"translator_type":"none"},{"id":1080477007580848128,"id_str":"1080477007580848128","name":"\u3042\u304d\u306a\u307f@\u30a6\u30a7\u30d6\u30ab\u30c4\u5f37\u304f\u3066\u30cb\u30e5\u30fc\u30b2\u30fc\u30e0\u4e2d","screen_name":"Arknanana","location":"","profile_location":null,"description":"\u5143\u696d\u52d9\u30b7\u30b9\u30c6\u30e0\u30d7\u30ed\u30b0\u30e9\u30de\u3002 \uff15\u5e74\u52d9\u3081\u305f\u4f1a\u793e\u3092\u9000\u8077\u3057\u305f\u300227\u6b73\u3002 \u751f\u304d\u308b\u306e\u306b\u6f70\u3057\u304c\u52b9\u304d\u305d\u3046\u306a\u3001Web\u30d7\u30ed\u30b0\u30e9\u30df\u30f3\u30b0\u3092\u7df4\u7fd2\u4e2d\u3002 Laravel\u3001WordPress\u3001vue\u3002\u672c\u3092\u3088\u304f\u8aad\u3080\u3002","url":null,"entities":{"description":{"urls":[]}},"protected":false,"followers_count":554,"friends_count":741,"listed_count":6,"created_at":"Wed Jan 02 14:52:47 +0000 2019","favourites_count":1112,"utc_offset":null,"time_zone":null,"geo_enabled":false,"verified":false,"statuses_count":271,"lang":null,"status":{"created_at":"Mon Sep 23 08:50:16 +0000 2019","id":1176056172438155264,"id_str":"1176056172438155264","text":"\u30e2\u30ce\u306e\u4e00\u90e8\u304c\u826f\u304f\u898b\u3048\u308b\u3068\u3001\u4ed6\u306e\u90e8\u5206\u3082\u826f\u3044\u3088\u3046\u306a\u6c17\u304c\u3057\u3066\u304f\u308b","truncated":false,"entities":{"hashtags":[],"symbols":[],"user_mentions":[],"urls":[]},"source":"\u003ca href=\"http:\/\/twitter.com\/download\/iphone\" rel=\"nofollow\"\u003eTwitter for iPhone\u003c\/a\u003e","in_reply_to_status_id":null,"in_reply_to_status_id_str":null,"in_reply_to_user_id":null,"in_reply_to_user_id_str":null,"in_reply_to_screen_name":null,"geo":null,"coordinates":null,"place":null,"contributors":null,"is_quote_status":false,"retweet_count":0,"favorite_count":3,"favorited":false,"retweeted":false,"lang":"ja"},"contributors_enabled":false,"is_translator":false,"is_translation_enabled":false,"profile_background_color":"000000","profile_background_image_url":"http:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png","profile_background_image_url_https":"https:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png","profile_background_tile":false,"profile_image_url":"http:\/\/pbs.twimg.com\/profile_images\/1169899435129630720\/iNmAXhAi_normal.jpg","profile_image_url_https":"https:\/\/pbs.twimg.com\/profile_images\/1169899435129630720\/iNmAXhAi_normal.jpg","profile_banner_url":"https:\/\/pbs.twimg.com\/profile_banners\/1080477007580848128\/1553213378","profile_link_color":"FAB81E","profile_sidebar_border_color":"000000","profile_sidebar_fill_color":"000000","profile_text_color":"000000","profile_use_background_image":false,"has_extended_profile":false,"default_profile":false,"default_profile_image":false,"following":null,"follow_request_sent":null,"notifications":null,"translator_type":"none"}]', true);
        return response()->json($result);
    }

    public function getSetting(Request $request)
    {
        $account_id = $request['account_id'];
        $setting = Auth::user()->accounts()->find($account_id)->accountSetting()->get();

        return response()->json($setting);
    }
    public function postSetting(Request $request)
    {
        if (empty($request['target_accounts'])) {
            $request['target_accounts'] = '';
        }
        $setting = Auth::user()->accounts()->find($request['account_id'])->accountSetting;
        $setting->fill($request->all())->save();
        return response()->json('hoge');
    }

    public function getTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        logger(Auth::user()->accounts()->find($account_id));
        $tweets = Auth::user()->accounts()->find($account_id)->reservedTweets()->orderBy('submit_date', 'desc')->get();
        return response()->json($tweets);
    }
    public function postTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        $tweet_id = $request['id'];
        logger($request);
        $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->updateOrcreate(['id' => $tweet_id], $request->all());
        // Auth::user()->accounts()->find($account_id)->reservedTweets()->save(new ReservedTweet($request->all()));
        // $tweet = new ReservedTweet($request->all());
        return response()->json($result);
    }
    public function destroyTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        $tweet_id = $request['id'];
        logger($request);
        logger(Auth::user()->accounts()->find($account_id));
        $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->find($tweet_id)->delete();
        return response()->json($result);
    }
}
