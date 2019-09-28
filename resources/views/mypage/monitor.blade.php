@extends('layouts.app')

@section('content')
<h2 class="c-title">稼働状況</h2>
<div class="c-row">
    <account-status-list></account-status-list>
</div>
@endsection

@section('sidebar')
@include('components.sidebar')
@endsection