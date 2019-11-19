<div id="sidebar">
    <h2 class="c-side-menu__title">{{Auth::user()->name}}さん</h2>
    <ul class="c-side-menu">
        <li class="c-side-menu__list"><a href="{{route('mypage.monitor')}}" class="c-side-menu__link"><i class="fas fa-desktop u-mr-2"></i>アカウント一覧・稼働状況</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.reserve')}}" class="c-side-menu__link"><i class="far fa-clock u-mr-2"></i>ツイート予約</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.account')}}" class="c-side-menu__link"><i class="fas fa-user-slash u-mr-2"></i>アカウント削除</a></li>
        <li class="c-side-menu__list"><a href="{{route('mypage.setting')}}" class="c-side-menu__link"><i class="fas fa-cog u-mr-2"></i>設定</a></li>
        <li class="c-side-menu__list"><a href="{{route('user.editinfo')}}" class="c-side-menu__link"><i class="fas fa-user-tag u-mr-2"></i>ユーザー情報編集</a></li>
        <li class="c-side-menu__list"><a href="{{route('user.editpass')}}" class="c-side-menu__link"><i class="fas fa-key u-mr-2"></i>パスワード変更</a></li>
        <li class="c-side-menu__list"><a href="{{route('user.retire')}}" class="c-side-menu__link"><i class="far fa-times-circle u-mr-2"></i>退会</a></li>
    </ul>
</div>