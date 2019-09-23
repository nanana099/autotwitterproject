@extends('layouts.app')

@section('content')

<form action="">
    <div class="p-select-account">
        <label for="" class="p-select-account__label">操作中のアカウント：
            <select name="" id="" class="p-select-account__select">
                <option value="">hoge</option>
                <option value="">fuga</option>
            </select>
        </label>
    </div>
</form>
<h2 class="c-title">自動ツイート予約</h2>
<div class="c-row">
    <form action="" class="p-tweet-form">
        <textarea name="" id="" class="p-tweet-form__textarea">

        </textarea>
        <span class="p-tweet-form__count js-show-count">140/140字</span>
        <div class="c-justify-content-between">
            <label for="">投稿予定日時：
                <input type="date" name="date" id="">
                <input type="time" name="time" id="">
            </label>
            <button class="c-btn c-btn--primary c-btn--large">予約</button>
        </div>
    </form>

    <h2 class="c-title">予約済みツイート</h2>
    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>

    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>

    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>

    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>

    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>

    <div class="p-reserve-history">
        <form action="">
            <p class="p-reserve-history__str">今日はいいてんきだ。<br>あしたもいいてんきにあるといいｆだいふぁ</p>
            <div class="c-justify-content-between">
                <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
            </div>

            <div class="c-justify-content-end">
                <button class="c-btn c-btn--primary">編集</button>
                <button class="c-btn c-btn--danger">削除</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection