@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="container c-justify-content-center">
        <div class="c-card u-w-70">
            <div class="c-card__header">
                ログイン
            </div>
            <div class="c-card__body">
                <div class="c-form-group">
                    <label for="email" class="c-form-group__label">e-mail</label>
                    <input id="email" type="email"
                        class="c-form-group__text form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="c-form-group ">
                    <label for="password" class="c-form-group__label">パスワード</label>

                    <input id="password" type="password"
                        class="c-form-group__text form-control @error('password') is-invalid @enderror" name="password"
                        required autocomplete="current-password">

                    @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="c-form-group">
                    <div class="">
                        <div class="">
                            <input class="" type="checkbox" name="remember" id="remember"
                                {{ old('remember') ? 'checked' : '' }}>

                            <label class="" for="remember">
                                次回ログインを省略する
                            </label>
                        </div>
                    </div>
                </div>

                <div class="c-justify-content-between">
                    @if (Route::has('password.request'))
                    <a class="" href="{{ route('password.request') }}">
                        パスワードを忘れた場合はこちら
                    </a>
                    @endif

                    <button type="submit" class="c-btn">
                        ログイン
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection