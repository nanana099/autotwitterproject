<header id="header">
    <div class="c-container c-justify-content-between c-align-item-center">
        <a class="c-site-logo" href="{{ route('top') }}">
            <h1 class="">
                    {{ config('app.name', '神ったー') }}
            </h1>
        </a>
        <nav class="c-gnav">
            <ul class="c-gnav__list c-justify-content-start ">
                <!-- Authentication Links -->
                @guest
                <li class="c-gnav__item"><a href="{{ route('login') }}" class="c-gnav__link">ログイン</a></li>
                @if (Route::has('register'))
                <li class="c-gnav__item"><a href="{{ route('register') }}" class="c-gnav__link">会員登録</a></li>
                @endif
                @else
                <li class="c-gnav__item">
                    <a class="c-gnav__link" href="{{ route('logout') }}" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                        ログアウト
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
                @endguest
            </ul>
        </nav>
    </div>
</header>