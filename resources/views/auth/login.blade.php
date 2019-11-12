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
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>

                <div class="c-form-group ">
                    <label for="password" class="c-form-group__label">パスワード</label>

                    <input id="password" type="password"
                        class="c-form-group__text form-control @error('password') is-invalid @enderror" name="password"
                        required autocomplete="current-password">

                    @error('password')
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
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

                <div class="u-mb-2 c-justify-content-end">

                    <button type="submit" class="c-btn">
                        ログイン
                    </button>
                </div>
                @if (Route::has('password.request'))
                <a class="u-d-block" href="{{ route('password.request') }}">
                    パスワードを忘れた場合はこちら
                </a>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection