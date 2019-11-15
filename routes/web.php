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

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('mypage.monitor');
    } else {
        return view('top');
    }
})->name('top');

Auth::routes();

Route::get('/help/keyword', function () {
    return view('help.keyword');
})->name('help.keyword');

Route::middleware(['auth'])->group(function () {
    Route::get('/mypage/monitor', function () {
        return view('mypage.monitor');
    })->name('mypage.monitor');

    Route::get('/mypage/reserve', function () {
        return view('mypage.reserve');
    })->name('mypage.reserve');

    Route::get('/mypage/account', function () {
        return view('mypage.account');
    })->name('mypage.account');

    Route::get('/mypage/setting', function () {
        return view('mypage.setting');
    })->name('mypage.setting');

    Route::get('/account/count', 'AccountController@count')->name('account.count');
    Route::get('/account/get', 'AccountController@get')->name('account.get');
    Route::get('/account/add', 'AccountController@add')->name('account.add');
    Route::delete('/account/destroy', 'AccountController@destroy')->name('account.destroy');
    Route::get('/account/setting', 'AccountController@getSetting')->name('account.setting.get');
    Route::post('/account/setting', 'AccountController@postSetting')->name('account.setting.post');
    Route::get('/account/status', 'AccountController@getStatus')->name('account.status.get');
    Route::post('/account/status', 'AccountController@postStatus')->name('account.status.post');
    Route::get('/account/tweet', 'AccountController@getTweet')->name('account.tweet.get');
    Route::post('/account/tweet', 'AccountController@postTweet')->name('account.tweet.post');
    Route::delete('/account/tweet', 'AccountController@destroyTweet')->name('account.tweet.destroy');
    Route::get('/account/callback', 'AccountController@callback');

    Route::get('/user/editinfo', 'UserController@getinfo')->name('user.getinfo');
    Route::post('/user/editinfo', 'UserController@editinfo')->name('user.editinfo');
    Route::get('/user/editpass', 'UserController@getpass')->name('user.getpass');
    Route::post('/user/editpass', 'UserController@editpass')->name('user.editpass');
});
