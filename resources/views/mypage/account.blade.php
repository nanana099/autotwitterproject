@extends('layouts.app')

@section('content')
<h2 class="c-title">アカウント追加/削除</h2>
<div class="c-row">
    <account-list></account-list>
    <div class="c-justify-content-end">
        <form action="{{route('account.add')}}" method="GET" class="p-monitor-list__form-group">
            <button class="c-btn c-btn--primary">追加</button>
        </form>
    </div>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection