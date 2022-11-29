<?php
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// TOP画面
Route::get('/', function () {
    return redirect()->route('mypage.monitor');
})->name('top');


Auth::routes(['verify' => true]);

// キーワード設定のためのヘルプ画面取得
Route::get('/help/keyword', function () {
    return view('help.keyword');
})->name('help.keyword');

Route::middleware(['auth','verified'])->group(function () {
    // アカウント一覧・稼働状況画面を返す
    Route::get('/mypage/monitor', function () {
        return view('mypage.monitor');
    })->name('mypage.monitor');

    // ツイート予約画面を返す
    Route::get('/mypage/reserve', function () {
        return view('mypage.reserve');
    })->name('mypage.reserve');

    // アカウント削除画面を返す
    Route::get('/mypage/account', function () {
        return view('mypage.account');
    })->name('mypage.account');

    // アカウントの設定画面を返す
    Route::get('/mypage/setting', function () {
        return view('mypage.setting');
    })->name('mypage.setting');

    // アカウントの登録数
    Route::get('/account/count', 'AccountController@count')->name('account.count');
    // アカウント情報の取得
    Route::get('/account/get', 'AccountController@get')->name('account.get');
    // アカウントの追加
    Route::get('/account/add', 'AccountController@add')->name('account.add');
    // アカウントの削除
    Route::delete('/account/destroy', 'AccountController@destroy')->name('account.destroy');
    // アカウントの設定情報取得
    Route::get('/account/setting', 'AccountController@getSetting')->name('account.setting.get');
    // アカウントの設定情報修正
    Route::post('/account/setting', 'AccountController@postSetting')->name('account.setting.post');
    // アカウントの自動機能の稼働状況取得
    Route::get('/account/status', 'AccountController@getStatus')->name('account.status.get');
    // アカウントの自動機能の稼働状況を変更
    Route::post('/account/status', 'AccountController@postStatus')->name('account.status.post');
    // アカウントのツイート予約状況を取得
    Route::get('/account/tweet', 'AccountController@getTweet')->name('account.tweet.get');
    // アカウントのツイート予約を実行
    Route::post('/account/tweet', 'AccountController@postTweet')->name('account.tweet.post');
    // アカウントのツイート予約を削除
    Route::delete('/account/tweet', 'AccountController@destroyTweet')->name('account.tweet.destroy');
    // TwitterAPIのコールバック
    Route::get('/account/callback', 'AccountController@callback');

    // ユーザー情報編集画面取得
    Route::get('/user/editinfo', 'UserController@getinfo')->name('user.getinfo');
    // ユーザー情報の変更
    Route::post('/user/editinfo', 'UserController@editinfo')->name('user.editinfo');
    // パスワード編集画面の取得
    Route::get('/user/editpass', 'UserController@getpass')->name('user.getpass');
    // パスワードの変更
    Route::post('/user/editpass', 'UserController@editpass')->name('user.editpass');
    // 退会画面取得
    Route::get('/user/retire',function () {
        return view('auth.retire');
    });
    // 退会処理
    Route::post('/user/retire', 'UserController@retire')->name('user.retire');
});
