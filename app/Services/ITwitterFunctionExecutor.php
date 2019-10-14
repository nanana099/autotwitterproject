<?php

namespace App\Services;

interface ITwitterFunctionExecutor
{
    public function prepare();

    public function execute();
}
