<?php

namespace App\Services;

// Twitterアカウントの操作を自動行うためのクラスのインターフェース
interface ITwitterFunctionExecutor
{
    public function prepare();

    public function execute();
}
