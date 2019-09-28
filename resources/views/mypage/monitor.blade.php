@extends('layouts.app')

@section('content')
<h2 class="c-title">稼働状況</h2>
<section class="p-section">
    <account-status-list></account-status-list>
    <div class="c-justify-content-start">
        <form action="{{route('account.add')}}" method="GET" class="p-monitor-list__form-group">
            <button class="c-btn c-btn--primary"><i class="fas fa-user-plus"></i>アカウント追加</button>
        </form>
    </div>
</section>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection