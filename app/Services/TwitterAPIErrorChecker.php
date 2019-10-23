<?php
namespace App\Services;

use App\Exceptions\TwitterRestrictionException;
use App\Exceptions\TwitterFlozenException;
use App\Exceptions\TwitterException;

// TwitterAPIレスポンスのエラーを見つけて、例外を発生するためのクラス
class TwitterAPIErrorChecker
{
    private const NOT_EXIST_PAGE = 34;          // 存在しないページを参照した
    private const ACCOUNT_SUSPENDED = 64;       // アカウントが凍結された
    private const RATE_LIMIT_EXCEEDED = 88;     // TwitterAPIのリクエスト制限が上限に達した
    private const EXPIRED_TOKEN_CODE = 89;      // アクセストークンの期限が切れている
    private const CANT_READ_BLOCKED = 179;      // ブロックされているためアクセス不可
    private const OVER_CAPACITY = 130;          // Twitterが高負荷状態
    private const USER_UPDATE_LIMITE_EXCEEDED = 185; // ユーザー投稿回数が制限を超えた
    private const DUPLICATE_POST_TWEET = 187;   // すでに投稿済みのつぶやきの投稿をした
    private const APP_FLOZEN_COEE = 261;        // アプリ自体が凍結された


    public static function check($result)
    {
        if (is_object($result)) {
            $result = get_object_vars($result);
        }
        if (!empty($result['errors'])) {
            logger($result['errors']);

            $errorCode = $result['errors'][0]->code;

            if ($errorCode === self::RATE_LIMIT_EXCEEDED) {
                throw new TwitterRestrictionException($errorCode); // 凍結中
            } elseif ($errorCode === self::ACCOUNT_SUSPENDED) {
                throw new TwitterFlozenException($errorCode); //API制限
            } else {
                throw new TwitterException($errorCode);// その他例外
            }
        }
    }
}
