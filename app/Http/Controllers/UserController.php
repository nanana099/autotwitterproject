<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserInfoPost;
use App\Http\Requests\UserPassPost;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // ユーザー情報の取得
    public function getinfo()
    {
        $user = Auth::user();
        $email = $user->email;
        $name = $user->name;

        return view('auth.editinfo', compact('email', 'name'));
    }

    // ユーザー情報を修正
    public function editinfo(UserInfoPost $request)
    {
        Auth::user()->fill($request->all())->save();

        return redirect()->route('mypage.monitor')->with('flash_message_success', 'ユーザー情報を更新しました。');
    }

    // パスワード変更画面を取得
    public function getpass()
    {
        return view('auth.editpass');
    }

    // パスワード変更
    public function editpass(UserPassPost $request)
    {
        // 現在のパスワードが正しいかを調べる
        if (!(Hash::check($request->get('password-current'), Auth::user()->password))) {
            return redirect()->back()->with('flash_message_error', '現在のパスワードが間違っています。');
        }

        // パスワードを変更
        $user = Auth::user();
        $user->password = bcrypt($request->get('password'));
        $user->save();

        return redirect()->route('mypage.monitor')->with('flash_message_success', 'パスワードを更新しました。');
    }

    // ユーザーの退会処理
    public function retire(Request $request)
    {
        try {
            $user = Auth::user();
            $accounts = $user->accounts()->get();
            DB::transaction(function () use ($accounts,$user) {
                foreach($accounts as $account){
                    // accountsテーブルに外部さん参照があるテーブルすべてを削除。
                    $account->operationStatus->delete();
                    $account->accountSetting->delete();
                    $account->reservedTweets()->delete();
                    $account->followedUsers()->delete();
                    $account->unfollowedUsers()->delete();
                    $account->delete();
                }
                $user->delete();
            });
            // return response()->json($account);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
        return redirect('/');
    }
    
}
