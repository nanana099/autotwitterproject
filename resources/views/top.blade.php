<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="description" content="お使いのTwitterアカウントを自動化するサービスです。フォロー、アンフォロー、いいねの自動化や、ツイート投稿の予約をご利用いただけます。">
    <meta name="keywords" content="Twitter,Twitter 自動化,ツイート 予約">

    <title>{{ config('app.name', '神ったー') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css"
        integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body>
    <div id="app">
        @include('components.header')
        <main class="c-container u-bg-main u-h-fluid">
            <div class="p-top__container">
                <div class="p-top__container--1">
                    <p class="p-top__paragraph1">
                        「フォロー」「フォロー」「フォロー」「フォロー」…<br>
                        「いいね」「いいね」「いいね」「いいね」…<br>
                        ………<br>……<br>…<br><br><br>
                    </p>
                    <p class="p-top__paragraph2">
                        「これ、人間の仕事じゃない。」
                    </p>
                </div>
                <div class="p-top__container--1">
                    <p class="p-top__paragraph3">
                        あなたに代わり、神ったーが不眠不休で<br>Twitterアカウントを運用します。
                    </p>
                    <p class="p-top__paragraph4">
                        ー自動化ー<br>
                        フォロー・アンフォロー・いいねを<br>設定1つで自動化します。<br>
                    </p>
                    <p class="p-top__paragraph4">
                        ー予約ー<br>
                        ツイートの投稿を予約できます。
                    </p>
                    <br><br>
                </div>
                <div class="p-top__container--1">
                    <a href="{{route('register')}}" class="c-btn c-btn--accent c-btn--large2 c-btn--circle"
                        style="text-decoration: none;">会員登録へ&#032;&#032;&gt;&gt;</a>
                </div>
            </div>
        </main>
        @include('components.footer')
    </div>

</body>

</html>