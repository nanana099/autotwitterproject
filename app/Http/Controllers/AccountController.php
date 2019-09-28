<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\TwitterAuth;
use App\Http\Services\TwitterAccount;
use App\Account;
use App\AccountSetting;
use App\OperationStatus;
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
            // DBの各テーブルへ行を挿入
            Account::updateOrCreate(['id' => $account_id], ['access_token' => $accessTokenStr,'user_id' => Auth::id(),'screen_name' => $screen_name, 'image_url' => $image_url]);
            AccountSetting::updateOrCreate(['account_id' => $account_id,'target_accounts' => '']);
            OperationStatus::updateOrCreate(['account_id' => $account_id]);

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



    public function getStatus(Request $request)
    {
        $status = Auth::user()->accounts()->with('operationStatus')->get();
        return response()->json($status);
    }
    public function postStatus(Request $request)
    {
        $type = $request['type'];
        $value = $request['value'];
        $account_id = $request['account_id'];
        logger($request);
        logger(Auth::user()->accounts()->find($account_id));

        $status = Auth::user()->accounts->find($account_id)->operationStatus;

        $data = array('account_id' => $account_id);
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

        return response()->json($status->fill($data)->save());
        // if (empty($request['target_accounts'])) {
        //     $request['target_accounts'] = '';
        // }
        // $setting = Auth::user()->accounts()->find($request['account_id'])->accountSetting;
        // $setting->fill($request->all())->save();
        // return response()->json('hoge');
    }
}
