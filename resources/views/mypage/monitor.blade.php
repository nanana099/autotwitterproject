@extends('layouts.app')

@section('content')
<h2 class="c-title">稼働状況</h2>
<div class="c-row">
    <ul class="p-monitor-list">
        <li class="p-monitor-list__item">
            <div class="p-monitor-list__account-info">
                <img src="https://iconbox.fun/wp/wp-content/uploads/106_h_24.svg" alt="" class="p-monitor-list__img">
                <span class="p-monito-list__user-name">
                    田中さん
                </span>
            </div>
            <div class="p-monitor-list__buttons">
                <form action="" class="p-monitor-list__form-group">
                    <label for="" class="p-monitor-list__form-label">フォロー</label>
                    <button class="c-btn c-btn--primary">稼働中</button>
                </form>
                <form action="" class="p-monitor-list__form-group">
                    <label for="" class="p-monitor-list__form-label">アンフォロー</label>
                    <button class="c-btn c-btn--primary">稼働中</button>
                </form>
                <form action="" class="p-monitor-list__form-group">
                    <label for="" class="p-monitor-list__form-label">いいね</label>
                    <button class="c-btn">停止済</button>
                </form>
            </div>
        </li>

        <ul class="p-monitor-list">
                <li class="p-monitor-list__item">
                    <div class="p-monitor-list__account-info">
                        <img src="https://iconbox.fun/wp/wp-content/uploads/106_h_24.svg" alt=""
                            class="p-monitor-list__img">
                        <span class="p-monito-list__user-name">
                            田中さん
                        </span>
                    </div>
                    <div class="p-monitor-list__buttons">
                        <form action="" class="p-monitor-list__form-group">
                            <label for="" class="p-monitor-list__form-label">フォロー</label>
                            <button class="c-btn c-btn--primary">稼働中</button>
                        </form>
                        <form action="" class="p-monitor-list__form-group">
                            <label for="" class="p-monitor-list__form-label">アンフォロー</label>
                            <button class="c-btn">停止済</button>
                        </form>
                        <form action="" class="p-monitor-list__form-group">
                            <label for="" class="p-monitor-list__form-label">いいね</label>
                            <button class="c-btn c-btn--primary">稼働中</button>
                        </form>
                    </div>
                </li>
            </ul>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection