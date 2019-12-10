@php
    $description = "Twitter自動化ツール「神ったー」に会員登録します。";
    $title = "会員登録";
@endphp
@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="container c-justify-content-center">
        <div class="c-card u-w-70">
            <div class="c-card__header">
                会員登録
            </div>
            <div class="c-card__body">
                <span class="u-fs-5"> *印は入力必須です。</span>
                <div class="c-form-group">
                    <label for="email" class="c-form-group__label">e-mail*</label>
                    <input id="email" type="email"
                        class="c-form-group__text form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>


                <div class="c-form-group">
                <label for="email" class="c-form-group__label">ユーザーID*<span class="u-fs-5">10文字以下で入力してください</span></label>
                    <input id="name" type="text"
                        class="c-form-group__text form-control @error('name') is-invalid @enderror" name="name"
                        value="{{ old('name') }}" required autocomplete="name">

                    @error('name')
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>

                <div class="c-form-group ">
                    <label for="password" class="c-form-group__label">パスワード*<span class="u-fs-5">8文字以上で入力してください</span></label>

                    <input id="password" type="password"
                        class="c-form-group__text form-control @error('password') is-invalid @enderror" name="password"
                        required autocomplete="current-password">

                    @error('password')
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>


                <div class="c-form-group ">
                    <label for="password-confirm" class="c-form-group__label">パスワード（再入力）*</label>

                    <input id="password-confirm" type="password" class="c-form-group__text form-control"
                        name="password_confirmation" required autocomplete="new-password">
                </div>


                <div class="c-justify-content-end">
                    <button type="submit" class="c-btn">
                        この内容で登録
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection