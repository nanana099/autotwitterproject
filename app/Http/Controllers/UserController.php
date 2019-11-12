<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserInfoPost;
use App\Http\Requests\UserPassPost;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getinfo()
    {
        $user = Auth::user();
        $email = $user->email;
        $name = $user->name;

        return view('auth.editinfo', compact('email', 'name'));
    }

    public function editinfo(UserInfoPost $request)
    {
        Auth::user()->fill($request->all())->save();

        return redirect()->route('mypage.monitor')->with('flash_message_success', 'ユーザー情報を更新しました。');
    }

    public function getpass()
    {
        return view('auth.editpass');
    }

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
}
