<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body>
    <div id="app">
        @include('components.header')

        <!-- フラッシュメッセージ -->
        @if (session('flash_message_success'))
        <div class="p-flash_message p-flash_message--success">
            {{ session('flash_message_success') }}
        </div>
        @endif
         <!-- フラッシュメッセージ -->
         @if (session('flash_message_error'))
         <div class="p-flash_message p-flash_message--error">
             {{ session('flash_message_error') }}
         </div>
         @endif
         
        
        <div class="c-container c-justify-content-center">
            <div id="main">
                @yield('content')
            </div>
            @yield('sidebar')
        </div>

        @include('components.footer')
    </div>
</body>

</html>