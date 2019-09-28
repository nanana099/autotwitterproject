<div id="sidebar">
    <h2 class="c-side-menu__title">{{Auth::user()->name}}さん</h2>
    <ul class="c-side-menu">
        <li class="c-side-menu__list"><a href="{{route('mypage.monitor')}}" class="c-side-menu__link"><i class="fas fa-desktop u-mr-2"></i>稼働状況</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.reserve')}}" class="c-side-menu__link"><i class="far fa-clock u-mr-2"></i>ツイート予約</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.account')}}" class="c-side-menu__link"><i class="fas fa-user-slash u-mr-2"></i>アカウント削除</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.setting')}}" class="c-side-menu__link"><i class="fas fa-cog u-mr-2"></i>設定</a></li>
    </ul>
</div>