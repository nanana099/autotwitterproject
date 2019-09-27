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
    return view('mypage.monitor');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('/mypage', function () {
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

    Route::get('/account/get', 'AccountController@get')->name('account.get');
    Route::get('/account/add', 'AccountController@add')->name('account.add');
    Route::delete('/account/destroy', 'AccountController@destroy')->name('account.destroy');
    Route::get('/account/setting', 'AccountController@getSetting')->name('account.setting.get');
    Route::post('/account/setting', 'AccountController@postSetting')->name('account.setting.post');
    Route::get('/account/tweet', 'AccountController@getTweet')->name('account.tweet.get');
    Route::post('/account/tweet', 'AccountController@postTweet')->name('account.tweet.post');
    Route::delete('/account/tweet', 'AccountController@destroyTweet')->name('account.tweet.destroy');
});
Route::get('/account/callback', 'AccountController@callback');
