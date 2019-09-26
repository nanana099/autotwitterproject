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

    Route::get('/account/add', 'AccountController@add')->name('account.add');
    Route::delete('/account/destroy', 'AccountController@destroy')->name('account.destroy');
});
Route::get('/account/callback', 'AccountController@callback');
