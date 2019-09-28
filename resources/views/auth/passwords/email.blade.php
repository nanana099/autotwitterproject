@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
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
                        value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <span class="c-c-invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>


                <div class="c-justify-content-end">
                    <button type="submit" class="c-btn">
                        この内容で送信
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection