<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwitterAuth;
use App\Services\TwitterAccount;
use App\Account;
use App\AccountSetting;
use App\OperationStatus;
use Illuminate\Support\Facades\Auth;
use App\ReservedTweet;

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

    public function count()
    {
        return response()->json(Auth::user()->accounts()->count());
    }

    public function callback()
    {
        $accessToken = TwitterAuth::getAccessToken();
        $twitter_user_id = $accessToken['user_id'];
        $account = Account::where('twitter_user_id', $twitter_user_id)->get();

        if (count($account) > 0 && $account[0]['user_id'] !== Auth::id()) {
            // すでにTwitterアカウントが他のユーザーによって登録済みの場合は不可
            return redirect()->route('mypage.monitor')->with('flash_message_error', 'Twitterアカウントが他のユーザにより登録済みのため、登録できませんでした。');
        } else {
            $accessTokenStr = json_encode($accessToken);

            $twitterAccount = new TwitterAccount($accessTokenStr);
            $twitterAccountInfo = $twitterAccount->getMyAccountInfo();
            $screen_name = $twitterAccountInfo['screen_name'];
            $image_url = $twitterAccountInfo['profile_image_url_https'];
        
            // mytodo: アクセストークン暗号化
            // DBの各テーブルへ行を挿入
            $hoge = Account::updateOrCreate(['twitter_user_id' => $twitter_user_id], ['access_token' => $accessTokenStr,'user_id' => Auth::id(),'screen_name' => $screen_name, 'image_url' => $image_url]);
            AccountSetting::firstOrCreate(['account_id' => $hoge['id']], ['target_accounts' => '']);
            OperationStatus::firstOrCreate(['account_id' =>$hoge['id']]);

            return redirect()->route('mypage.monitor')->with('flash_message_success', 'Twitterアカウントの登録に成功しました。');
        }
    }

    public function destroy(Request $request)
    {
        $account = Auth::user()->accounts()->find($request['id']);
        if (true) {
            $account->operationStatus->delete();
            $account->accountSetting->delete();
            $account->reservedTweets()->delete();
            $account->delete();
            return response()->json($account);
        } else {
            return response()->json($account);
        }
    }

    public function get()
    {
        $accounts = Auth::user()->accounts()->get();
        return response()->json($accounts);
    }

    public function getSetting(Request $request)
    {
        $account_id = $request['account_id'];
        $setting = Auth::user()->accounts()->find($account_id)->accountSetting()->get();

        return response()->json($setting);
    }
    public function postSetting(Request $request)
    {
        // 空欄で送信されるとnullになる。DB上null非許容なので、空文字を入れておく。
        if (empty($request['target_accounts'])) {
            $request['target_accounts'] = '';
        }
        if (empty($request['keyword_follow'])) {
            $request['keyword_follow'] = '';
        }
        if (empty($request['keyword_favorite'])) {
            $request['keyword_favorite'] = '';
        }

        $setting = Auth::user()->accountAccountSetting()->find($request['account_setting_id']);
        return response()->json($setting->fill($request->all())->save());
    }

    public function getTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        $tweets = Auth::user()->accounts()->find($account_id)->reservedTweets()->orderBy('submit_date', 'desc')->get();
        return response()->json($tweets);
    }
    public function postTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        $tweet_id = $request['reserved_tweet_id'];
        $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->updateOrcreate(['id' => $tweet_id], $request->all());
        return response()->json($result);
    }
    public function destroyTweet(Request $request)
    {
        $account_id = $request['account_id'] ;
        $tweet_id = $request['id'];
        $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->find($tweet_id)->delete();
        return response()->json($result);
    }

    public function getStatus(Request $request)
    {
        $status = Auth::user()->accounts()->with('operationStatus')->get();
        return response()->json($status);
    }
    public function postStatus(Request $request)
    {
        $type = $request['type'];
        $value = $request['value'];
        $operation_status_id = $request['operation_status_id'];
        $status = Auth::user()->accountOperationStatus()->find($operation_status_id);
        $data = array();
        switch ($type) {
            case 'follow':
                $data['is_follow'] = $value;
                break;
            case 'unfollow':
                $data['is_unfollow'] = $value;
                break;
            case 'favorite':
                $data['is_favorite'] = $value;
                break;
        }
        $data['is_flozen'] = false;
        return response()->json($status->fill($data)->save());
    }
}
