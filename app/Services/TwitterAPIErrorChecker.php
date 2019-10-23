<?php
namespace App\Services;

use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use \Exception;

// TwitterAPIレスポンスのエラーを見つけて、例外を発生するためのクラス
class TwitterAPIErrorChecker
{
    private const FLOZEN_COEE = 999; // リクエストを実行したTwitterアカウントが凍結中
    private const RISTRICT_CODE = 429; // リクエストを実行したTwitterアカウントがAPI制限を受けている

    public static function check($result)
    {
        if (is_object($result)) {
            $result = get_object_vars($result);
        }
        if (!empty($result['errors'])) {
            logger($result['errors']);

            $errorCode = $result['errors'][0]->code;

            if ($errorCode === self::RISTRICT_CODE) {
                throw new TwitterRestrictionException(); // 凍結中
            } elseif ($errorCode === self::FLOZEN_COEE) {
                throw new TwitterFlozenException(); //API制限
            } else {
                throw new Exception();// その他例外
            }
        }
    }
}
