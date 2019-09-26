<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\TwitterAuth;
use App\User;
use App\Account;
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

        if (!empty($account) && $account['user_id'] !== 2) {
            // すでにTwitterアカウントが他のユーザーによって登録済みの場合は不可
            return redirect()->route('mypage.account')->with('flash_message_error', 'Twitterアカウントが登録済みのため、登録できませんでした。');
        } else {
            $accessTokenStr = json_encode(TwitterAuth::getAccessToken());
            // Todo: アクセストークン暗号化
            Account::updateOrCreate(['id' => $account_id], ['access_token' => $accessTokenStr,'user_id' => Auth::id()]);
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
}
