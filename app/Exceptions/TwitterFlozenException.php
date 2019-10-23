<?php

namespace App\Exceptions;

use App\Exceptions\TwitterException;

class TwitterFlozenException extends TwitterException
{
    private $errorCode;

    public function setErrorCode(int $errorCode) {
        $this->errorCode = $errorCode;
    }
    public function getErrorCode(int $errorCode){
        return $this->errorCode;
    }
}
