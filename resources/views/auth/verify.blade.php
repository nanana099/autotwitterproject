@php
$title = "メールアドレス認証";
@endphp
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header u-mb-3">メールアドレスを認証してください。</div>

                <div class="card-body">
                    @if (session('resent'))
                    <div class="alert alert-success u-mb-1" role="alert">
                        新規の認証リンクを再度送信しました。
                    </div>
                    @endif
                    <p class="u-mb-3">
                        送信メール内の認証リンクから認証を行ってください。</p>
                    <p class="u-mb-1"> メールが届いていない場合は、</p>
                </div>
                <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 m-0 align-baseline">こちらをクリックして再度メールを送信します。</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection