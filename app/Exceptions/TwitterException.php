<?php

namespace App\Exceptions;

use Exception;

class TwitterException extends Exception
{
    protected $errorCode;

    public function __construct(int $errorCode)
    {
        $this->errorCode = $errorCode;
    }

    public function errorCode()
    {
        return $this->errorCode;
    }
}
