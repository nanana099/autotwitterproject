@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="container c-justify-content-center">
        <div class="c-card u-w-70">
            <div class="c-card__header">
                パスワードリセット
            </div>
            <div class="c-card__body">
                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif

                <div class="c-form-group">
                    <label for="email" class="c-form-group__label">e-mail</label>
                    <input id="email" type="email"
                        class="c-form-group__text form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
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


                <div class="c-form-group ">
                    <label for="password-confirm" class="c-form-group__label">パスワード（再入力）</label>

                    <input id="password-confirm" type="password" class="c-form-group__text form-control"
                        name="password_confirmation" required autocomplete="new-password">
                </div>


                <div class="c-justify-content-end">
                    <button type="submit" class="c-btn">
                        パスワードを変更
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection