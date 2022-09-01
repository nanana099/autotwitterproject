<div id="sidebar" class="js-target-sidebar"> <h2 class="c-side-menu__title">{{Auth::user()->name}}さん</h2>
    <ul class="c-side-menu">
        <li class="c-side-menu__list"><i class="fas fa-desktop u-mr-2 c-side-menu__icon"></i><a href="{{route('mypage.monitor')}}" class="c-side-menu__link">アカウント一覧・稼働状況</a></li>
        <li class="c-side-menu__list"><i class="fas fa-cog u-mr-2"></i><a href="{{route('mypage.setting')}}" class="c-side-menu__link">自動機能設定</a></li>
        <li class="c-side-menu__list"><i class="far fa-clock u-mr-2"></i><a href="{{route('mypage.reserve')}}" class="c-side-menu__link">ツイート予約</a></li>
        <li class="c-side-menu__list"><i class="fas fa-user-slash u-mr-2"></i><a href="{{route('mypage.account')}}" class="c-side-menu__link">Twitterアカウント登録削除</a></li>
        <li class="c-side-menu__list"><i class="fas fa-user-tag u-mr-2"></i><a href="{{route('user.editinfo')}}" class="c-side-menu__link">ユーザー情報編集</a></li>
        <li class="c-side-menu__list"><i class="fas fa-key u-mr-2"></i><a href="{{route('user.editpass')}}" class="c-side-menu__link">パスワード変更</a></li>
        <li class="c-side-menu__list"><i class="far fa-times-circle u-mr-2"></i><a href="{{route('user.retire')}}" class="c-side-menu__link">退会</a></li>
    </ul>
</div>

<div class="js-open-sidebar c-side-menu__btn-open">MENU</div>
<div class="js-close-sidebar c-side-menu__btn-close">CLOSE</div>