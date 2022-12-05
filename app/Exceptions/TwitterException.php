<?php

namespace App\Exceptions;

use Exception;

// TwitterAPIからエラーが返る場合に例外をスローする。その場合にスローする例外の基底クラス
class TwitterException extends Exception
{
    protected $errorCode;

    public function __construct(int $errorCode = -1)
    {
        $this->errorCode = $errorCode;
    }

    public function errorCode()
    {
        return $this->errorCode;
    }
}
