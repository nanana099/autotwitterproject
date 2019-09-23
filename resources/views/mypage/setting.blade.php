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
<h2 class="c-title">設定</h2>
<div class="c-row">
    <form action="">
        <fieldset class="c-form-fieldset">
                <legend>自動フォロー関連</legend>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・フォローキーワード</label>
                <input id="email" type="email"
                    class="c-form-group__text form-control @error('email') is-invalid @enderror" name="email"
                    value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・ターゲットアカウント</label>
                <select id="list2" name="list2" size="5" class="c-form-group__select-multi u-mb-3" multiple>
                    <option value="1">abc_iii</option>
                    <option value="1">abc_iii</option>
                    <option value="1">abc_iii</option>
                    <option value="1">abc_iii</option>
                    <option value="1">abc_iii</option>
                    <option value="1">ddssssfadf</option>
                    <option value="1">fadfafcc</option>
                    <option value="1">abfdafafccc_iii</option>
                    <option value="1">efafeafeee</option>
                </select>
                <div class="c-justify-content-end">
                    <label for="">追加するアカウント名：
                        <input id="email" type="email" class=" form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ old('email') }}" required autocomplete="email" autofocus></label>
                    <button class="c-btn c-btn--primary">追加</button>
                    <button class="c-btn c-btn--danger">削除</button>
                </div>
            </div>
        </fieldset>
        <fieldset class="c-form-fieldset">
                <legend>自動アンフォロー関連</legend>
            <div class="c-form-group">
                <label for="email" class="">・フォローしてから
                    <input id="" type="number" class=" form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required autocomplete="email" autofocus>
                    日間、フォローが無かったらアンフォローする</label>
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="c-form-group">
                <label for="unfollow-inactive" class="">
                    ・<input type="checkbox" name="unfollow-inactive" id="unfollow-inactive">
                    非アクティブのユーザーのフォローを外す
                </label>
            </div>
        </fieldset>
        <fieldset class="c-form-fieldset">
                <legend>自動いいね関連</legend>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・いいねキーワード</label>
                <input id="email" type="email"
                    class="c-form-group__text form-control @error('email') is-invalid @enderror" name="email"
                    value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </fieldset>

        <div class="c-justify-content-end">
            <button class="c-btn c-btn--primary c-btn--large u-mr-2">保存</button>
        </div>
    </form>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection