<?php

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
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/mypage', function () {
    if (Auth::check()) {
        return view('mypage.monitor');
    }else{
        return redirect()->route('login');
    }
})->name('mypage.monitor');


Route::get('/mypage/reserve', function () {
    if (Auth::check()) {
        return view('mypage.reserve');
    }else{
        return redirect()->route('login');
    }
})->name('mypage.reserve');



Route::get('/mypage/account', function () {
    if (Auth::check()) {
        return view('mypage.account');
    }else{
        return redirect()->route('login');
    }
})->name('mypage.account');

Route::get('/mypage/setting', function () {
    if (Auth::check()) {
        return view('mypage.setting');
    }else{
        return redirect()->route('login');
    }
})->name('mypage.setting');
