<?php
namespace App\Services;

use \Exception;
use Illuminate\Support\Facades\DB;
use App\OperationStatus;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlainText;

// 自動いいね実行クラス
class MailSender
{
    public const EMAIL_FLOZEN = 0;
    public const EMAIL_FOLLOW_COMPLATED = 1;
    public const EMAIL_UNFOLLOW_COMPLATED = 2;
    public const EMAIL_FOLLOW_RESTRICTED = 3;
    public const EMAIL_UNFOLLOW_RESTRICTED = 4;

    private const EMAIL_FLOZEN_SUBJECT = 'アカウントが凍結されました';
    private const EMAIL_FOLLOW_COMPLATED_SUBJECT = '自動フォローが完了しました';
    private const EMAIL_FOLLOW_RESTRICTED_SUBJECT = '自動フォローが中断されました';
    private const EMAIL_UNFOLLOW_COMPLATED_SUBJECT = '自動アンフォローが完了しました';
    private const EMAIL_UNFOLLOW_RESTRICTED_SUBJECT = '自動アンフォローが中断されました';

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

    private const EMAIL_UNFOLLOW_RESTRICTED_COTENT = <<< EOM

    
いつもご利用いただきありがとうございます。


以下のTwitterアカウントの自動アンフォローが中断されました。
・@%s


再開までしばらくお待ち下さい。


EOM;

    private const EMAIL_FOLLOW_RESTRICTED_COTENT = <<< EOM

    
いつもご利用いただきありがとうございます。

以下のTwitterアカウントの自動フォローが中断されました。
・@%s

再開までしばらくお待ち下さい。


EOM;

    public static function send($userName, $accountName, $email, $pattern)
    {
        $content = sprintf(self::EMAIL_USERNAME, $userName);
        switch ($pattern) {
            case  self::EMAIL_FOLLOW_COMPLATED:// フォロー完了
                $subject = self::EMAIL_FOLLOW_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_FOLLOW_COMPLATED_COTENT, $accountName);
                break;

            case  self::EMAIL_FOLLOW_RESTRICTED:// フォロー中断
                $subject = self::EMAIL_FOLLOW_RESTRICTED_SUBJECT;
                $content .= sprintf(self::EMAIL_FOLLOW_RESTRICTED_COTENT, $accountName);
            break;

            case  self::EMAIL_UNFOLLOW_COMPLATED:// アンフォロー完了
                $subject = self::EMAIL_UNFOLLOW_COMPLATED_SUBJECT;
                $content .= sprintf(self::EMAIL_UNFOLLOW_COMPLATED_COTENT, $accountName);
            break;

            case  self::EMAIL_UNFOLLOW_RESTRICTED:// アンフォロー完了
                $subject = self::EMAIL_UNFOLLOW_RESTRICTED_SUBJECT;
                $content .= sprintf(self::EMAIL_UNFOLLOW_RESTRICTED_COTENT, $accountName);
            break;


            case self::EMAIL_FLOZEN:// 凍結された
                $subject = self::EMAIL_FLOZEN_SUBJECT;
                $content .= sprintf(self::EMAIL_FLOZEN_CONTENT, $accountName);
                break;
        }

        $to = $email;
        Mail::to($to)->send(new PlainText($subject, $content));
    }
}
