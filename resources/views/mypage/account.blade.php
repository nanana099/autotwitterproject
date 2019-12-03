@php
    $description = "Twitter自動化ツール「神ったー」に登録したアカウントを削除します。"
@endphp
@extends('layouts.app')

@section('content')
<h2 class="c-title">アカウント削除</h2>
<section class="p-section">
    <account-list></account-list>
</section>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection