<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserInfoPost;
use App\Http\Requests\UserPassPost;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\EmailReset;
use Carbon\Carbon;
use App\User;

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
        $data['name'] = $request->name;

        $new_email = $request->email;


        if (Auth::user()->email === $new_email) {
            Auth::user()->fill($data)->save();
            return redirect()->route('mypage.monitor')->with('flash_message_success', 'ユーザー情報を更新しました。');
        } else {
            $flashMessage = '';
            if (Auth::user()->name === $request->name) {
                $flashMessage = 'Emailアドレスを更新するため、確認メールを送信しましたのでご確認ください。';
            } else {
                $flashMessage = 'ユーザーIDを更新しました。Emailアドレスを更新するため、確認メールを送信しましたのでご確認ください。';
            }

            //  トークン生成
            $token = hash_hmac(
                'sha256',
                Str::random(40) . $new_email,
                config('app.key')
            );

            // トークンをDBに保存
            DB::beginTransaction();
            try {
                $param = [];
                $param['user_id'] = Auth::id();
                $param['new_email'] = $new_email;
                $param['token'] = $token;
                $email_reset = EmailReset::create($param);

                Auth::user()->fill($data)->save();

                DB::commit();

                $email_reset->sendEmailResetNotification($token);

                return redirect()->route('mypage.monitor')->with('flash_message_success', $flashMessage);
            } catch (\Exception $e) {
                DB::rollback();
                dd($e);
                return redirect()->route('mypage.monitor')->with('flash_message_success', 'ユーザー情報更新に失敗しました。');
            }
        }
    }

    /**
     * メールアドレスの再設定処理
     *
     * @param Request $request
     * @param [type] $token
     */
    public function changeEmail(Request $request, $token)
    {
        $email_resets = DB::table('email_resets')
            ->where('token', $token)
            ->first();

        // トークンが存在している、かつ、有効期限が切れていないかチェック
        if ($email_resets && !$this->tokenExpired($email_resets->created_at)) {

            // ユーザーのメールアドレスを更新
            $user = User::find($email_resets->user_id);
            $user->email = $email_resets->new_email;
            $user->save();

            // レコードを削除
            DB::table('email_resets')
                ->where('token', $token)
                ->delete();

            return redirect()->route('mypage.monitor')->with('flash_message_success', 'メールアドレスを更新しました。');
        } else {
            // レコードが存在していた場合削除
            if ($email_resets) {
                DB::table('email_resets')
                    ->where('token', $token)
                    ->delete();
            }
            return redirect()->route('mypage.monitor')->with('flash_message_success', 'メールアドレスの更新に失敗しました。');
        }
    }


    /**
     * トークンが有効期限切れかどうかチェック
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenExpired($createdAt)
    {
        // トークンの有効期限は60分に設定
        $expires = 60 * 60;
        return Carbon::parse($createdAt)->addSeconds($expires)->isPast();
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
            DB::transaction(function () use ($accounts, $user) {
                foreach ($accounts as $account) {
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
