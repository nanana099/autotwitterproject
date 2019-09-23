<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\TwitterAuth;

class TwitterController extends Controller
{
    public function auth()
    {
        $authUrl = TwitterAuth::getAuthorizeUrl();
        return redirect($authUrl);
    }

    public function callback()
    {
        $accessToken = TwitterAuth::getAccessToken();
        // DBへ格納
        // ログイン中のユーザーIDとuser_id,accesstoken(暗号化する)を格納
        return redirect()->route('mypage.monitor');
    }
}
