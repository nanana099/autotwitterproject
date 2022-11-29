<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Facades\DB;
use App\OperationStatus;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlainText;

// メール送信クラス
class MailSender
{
    public const EMAIL_FLOZEN = 0;
    public const EMAIL_FOLLOW_COMPLATED = 1;
    public const EMAIL_UNFOLLOW_COMPLATED = 2;
    public const EMAIL_FAVORITE_COMPLATED = 3;
    public const EMAIL_TWEET_COMPLATED = 4;
    public const AUTH_EXIPIRED = 5;
    public const NOTICE_ERROR = 999;

    private const EMAIL_FLOZEN_SUBJECT = 'アカウントが凍結されました';
    private const EMAIL_FOLLOW_COMPLATED_SUBJECT = '自動フォローが完了しました';
    private const EMAIL_UNFOLLOW_COMPLATED_SUBJECT = '自動アンフォローが完了しました';
    private const EMAIL_FAVORITE_COMPLATED_SUBJECT = '自動いいねが完了しました';
    private const EMAIL_TWEET_COMPLATED_SUBJECT = 'ツイートを投稿しました。';
    private const AUTH_EXIPIRED_SUBJECT = 'アカウントの連携が切断されました。';
    private const NOTICE_ERROR_SUBJECT = 'TwitterAPIで例外';

    private const EMAIL_USERNAME = '%s 様';

    private const EMAIL_FLOZEN_CONTENT = <<< EOM

    
    いつもご利用いただきありがとうございます。
    
    以下のTwitterアカウントが凍結されたため、
    神ったーのすべての自動機能を停止いたしました。
    ・@%s

    以下の手順で自動機能を復旧させてください。
    ①Twitterにアクセスし、アカウントの凍結を解除する
    ②神ったーにアクセスし、稼働状況画面より自動機能を稼働させる
    
    
    以上、よろしくお願いいたします。

EOM;

    private const EMAIL_FOLLOW_COMPLATED_COTENT = <<< EOM

    
    いつもご利用いただきありがとうございます。
    
    以下のTwitterアカウントの自動フォローが完了しました。
    ・@%s

EOM;

    private const EMAIL_UNFOLLOW_COMPLATED_COTENT = <<< EOM


いつもご利用いただきありがとうございます。

以下のTwitterアカウントの自動アンフォローが完了しました。
・@%s

EOM;

    private const EMAIL_FAVORITE_COMPLATED_COTENT = <<< EOM

    
いつもご利用いただきありがとうございます。

以下のTwitterアカウントの自動いいねが完了しました。
・@%s

EOM;

    private const EMAIL_TWEET_COMPLATED_COTENT = <<< EOM


いつもご利用いただきありがとうございます。

以下のTwitterアカウントにてツイートを投稿しました。
・@%s

EOM;

    private const AUTH_EXIPIRED_COTENT = <<< EOM


いつもご利用いただきありがとうございます。

神ったーと以下のTwitterアカウントの連携が切断されました。
・@%s

つきましては、以下の手順で復旧をよろしくお願いいたします。
①神ったーへログインする
②アカウント一覧・稼働状況画面を開く
③アカウント追加ボタンを押す
④「連携アプリを認証」ボタンを押す

EOM;


    private const NOTICE_ERROR_COTENT = <<< EOM

予期せぬ例外が発生しました。
%s

EOM;

    public static function send($userName, $accountName, $email, $pattern)
    {
        $content = sprintf(self::EMAIL_USERNAME, $userName);
        switch ($pattern) {
            case  self::EMAIL_FOLLOW_COMPLATED:// フォロー完了
                $subject = self::EMAIL_FOLLOW_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_FOLLOW_COMPLATED_COTENT, $accountName);
                break;
            case  self::EMAIL_UNFOLLOW_COMPLATED:// アンフォロー完了
                $subject = self::EMAIL_UNFOLLOW_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_UNFOLLOW_COMPLATED_COTENT, $accountName);
            break;
            case  self::EMAIL_FAVORITE_COMPLATED:// いいね完了
                $subject = self::EMAIL_FAVORITE_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_FAVORITE_COMPLATED_COTENT, $accountName);
            break;
            case  self::EMAIL_TWEET_COMPLATED:// ツイート完了
                $subject = self::EMAIL_TWEET_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_TWEET_COMPLATED_COTENT, $accountName);
            break;
            case self::EMAIL_FLOZEN:// 凍結された
                $subject = self::EMAIL_FLOZEN_SUBJECT;
                $content .= sprintf(self::EMAIL_FLOZEN_CONTENT, $accountName);
                break;

            case self::AUTH_EXIPIRED:// 凍結された
                $subject = self::AUTH_EXIPIRED_SUBJECT;
                $content .= sprintf(self::AUTH_EXIPIRED_COTENT, $accountName);
                break;

            case self::NOTICE_ERROR:// 予期せぬ例外
                $subject = self::NOTICE_ERROR_SUBJECT;
                $content .= sprintf(self::NOTICE_ERROR_COTENT, $accountName);
                break;
        }

        $to = $email;
        if (env('APP_ENV') === 'local') {
            logger('メール送信内容：'.' ユーザー名：'.$userName.' アカウント名：'.$accountName.' メールアドレス：'.$email.' 送信内容：'.$content);
            Mail::to($to)->send(new PlainText($subject, $content));
        } else {
            Mail::to($to)->send(new PlainText($subject, $content));
        }
    }
}
