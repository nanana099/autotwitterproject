@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('user.retire') }}">
    @csrf
    <div class="container c-justify-content-center">
        <div class="c-card u-w-70">
            <div class="c-card__header">
                退会処理
            </div>
            <div class="c-card__body">
                <p class="u-fs-5 u-mb-3">退会すると、神ったーに登録済みの情報すべてが消去されます。
                </p>

                <p class="u-fs-5 u-mb-3">退会しますか？
                </p>

                <div class="c-justify-content-end">
                    <button type="submit" class="c-btn" onclick="return confirm('退会します。本当に、よろしいですか？')">
                        退会する
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@section('sidebar')
@include('components.sidebar')
@endsection