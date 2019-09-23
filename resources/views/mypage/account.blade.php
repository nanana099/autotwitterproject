@extends('layouts.app')

@section('content')
<h2 class="c-title">アカウント追加/削除</h2>
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
                    <button class="c-btn c-btn--danger">削除</button>
                </form>
            </div>
        </li>

        <li class="p-monitor-list__item">
            <div class="p-monitor-list__account-info">
                <img src="https://iconbox.fun/wp/wp-content/uploads/106_h_24.svg" alt="" class="p-monitor-list__img">
                <span class="p-monito-list__user-name">
                    abc999
                </span>
            </div>
            <div class="p-monitor-list__buttons">
                <form action="" class="p-monitor-list__form-group">
                    <button class="c-btn c-btn--danger">削除</button>
                </form>
            </div>
        </li>
    </ul>

    <div class="c-justify-content-end">
        <form action="" class="p-monitor-list__form-group">
            <button class="c-btn c-btn--primary">追加</button>
        </form>
    </div>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection