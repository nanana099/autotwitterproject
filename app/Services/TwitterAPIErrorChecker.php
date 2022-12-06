<?php
namespace App\Services;

use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterException;
use App\Exceptions\TwitterAuthExipiredException;
use \Exception;

// TwitterAPIレスポンスのエラーを見つけて、例外を発生するためのクラス
class TwitterAPIErrorChecker
{
    private const ACCOUNT_SUSPENDED = 64;       // アカウントが凍結された
    private const RATE_LIMIT_EXCEEDED = 88;     // TwitterAPIのリクエスト制限が上限に達した
    private const AUTH_EXIPIRED = 89;     // TwitterAPIのリクエスト制限が上限に達した
    private const CANT_FOLLOW_TEMPORARY = 161;      // 一時的にフォロー不可
    private const OVER_CAPACITY = 130;          // Twitterが高負荷状態
    private const FLOZEN = 326;          // 凍結中アカウント

    public static function check($result)
    {
        if (is_object($result)) {
            $result = get_object_vars($result);
        }
        if (!empty($result['errors'])) {
            logger()->debug($result['errors']);

            $errorCode = $result['errors'][0]->code;

            switch ($errorCode) {
                case self::RATE_LIMIT_EXCEEDED:
                    throw new TwitterRestrictionException($errorCode);
                    break;
                case self::CANT_FOLLOW_TEMPORARY:
                    throw new TwitterRestrictionException($errorCode);
                    break;
                case self::OVER_CAPACITY:
                    throw new TwitterRestrictionException($errorCode);
                    break;
                case self::ACCOUNT_SUSPENDED:
                    throw new TwitterFlozenException($errorCode);
                    break;
                case self::FLOZEN:
                    throw new TwitterFlozenException($errorCode);
                    break;
                case self::AUTH_EXIPIRED:
                    throw new TwitterAuthExipiredException($errorCode);
                    break;
                default:
                    try {
                        MailSender::send('ご担当者', 'error:'.$errorCode, config('app.operator_email'), MailSender::NOTICE_ERROR);
                    } catch (Exception $e) {
                    }
                    throw new TwitterException($errorCode);// その他例外
                    break;
            }
        }
    }
}
