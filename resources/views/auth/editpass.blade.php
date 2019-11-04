@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('user.editpass') }}">
    @csrf
    <div class="container c-justify-content-center">
        <div class="c-card u-w-70">
            <div class="c-card__header">
                パスワード変更
            </div>
            <div class="c-card__body">

                <div class="c-form-group ">
                    <label for="password-current" class="c-form-group__label">現在のパスワード</label>

                    <input id="password-current" type="password"
                        class="c-form-group__text form-control @error('password-current') is-invalid @enderror"
                        name="password-current" required>

                    @error('password-current')
                    <span class="c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>

                <div class="c-form-group ">
                    <label for="password" class="c-form-group__label">新しいパスワード</label>

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
                    <label for="password-confirm" class="c-form-group__label">新しいパスワード（再入力）</label>

                    <input id="password-confirm" type="password" class="c-form-group__text form-control"
                        name="password_confirmation" required autocomplete="new-password">
                </div>


                <div class="c-justify-content-end">
                    <button type="submit" class="c-btn">
                        この内容で登録
                    </button>
                </div>

                <a class="u-d-block" href="{{ route('mypage.monitor') }}">
                    &lt;&#032;TOPへ戻る
                </a>
            </div>
        </div>
    </div>
</form>
@endsection