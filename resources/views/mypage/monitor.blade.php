@php
    $description = "Twitter自動化ツール「神ったー」に登録したアカウントの一覧と自動化機能の稼働状況を確認します。";
    $title = "アカウント一覧・稼働状況";
@endphp
@extends('layouts.app')

@section('content')
    <account-status-list></account-status-list>
</section>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection