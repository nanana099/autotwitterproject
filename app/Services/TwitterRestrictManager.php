<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\TwitterapiCalledLog;
use App\Exceptions\TwitterRestrictionException;

// TwitterAPIのリソースのうち、「application/rate_limit_status（TwitteAPIリソースの残り利用回数を確認できるAPI）」で
// 残り利用可能回数を取得できないリソースの利用を管理するためのクラス
class TwitterRestrictManager
{
    // twitterのuser_id
    private $user_id;
    // 一部TwitterAPIのリソースの使用状況管理用の定数と変数
    private const FRIENDSHIPS_CREATE    = 'friendships/create';
    private const FRIENDSHIPS_DESTROY   = 'friendships/destroy';
    private const STATUSES_UPDATE       = 'statuses/update';
    private const FAVORITES_CREATE      = 'favorites/create';
    // 自前で呼び出し回数を制限したいリソース名
    private const MANAGE_LIMIT_RESOURCE =[self::FRIENDSHIPS_CREATE,self::FRIENDSHIPS_DESTROY,self::STATUSES_UPDATE,self::FAVORITES_CREATE];
    // friendships/createの上限
    private const FRIENDSHIPS_CREATE_LIMIT_PER_15MINUTE_ACCOUNT = 15;    // friendships/createの15分上限（１アカウント）
    private const FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR_ACCOUNT = 500;     // friendships/createの２４時間上限（１アカウント）
    private const FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR_APP = 500;         // friendships/createの２４時間上限（アプリ全体）
    // friendships/destroyの上限
    private const FRIENDHSIPS_DESTROY_LIMIT_PER_24HOUR_ACCOUNT = 1000;  // friendships/destroyの２４時間上限（１アカウント）
    // statuses/updateの上限
    private const STATUSES_UPDATE_LIMIT_PER_3HOUR_APP = 300;            // statuss/updateの上限（アプリ全体）
    // favorites/createの上限
    private const FAVORITES_CREATE_LIMIT_PER_24HOUR_APP = 1000;         // favorites/createの24時間上限（アプリ全体）

    // friendships/createの利用回数格納用
    private $calledCountFriendshipsCreateBefore15Minute;
    private $calledCountFriendshipsCreateBefore24Hour;
    private $calledCountFriendshipsCreateBefore24HourApp;
    private $calledCountFriendshipsCreateNow;
    // friendships/destroyの利用回数格納用
    private $calledCountFriendshipsDestroyBefore15Minute;
    private $calledCountFriendshipsDestroyBefore24Hour;
    private $calledCountFriendshipsDestroyNow;
    // statuses/updateの利用回数格納用
    private $calledCountStatusesUpdateBefore3HourApp;
    private $calledCountStatusesUpdateNow;
    // favorites/createの利用回数格納用
    private $calledCountFavoritesCreateBefore24HourApp;
    private $calledCountFavoritesCreateNow;
   
    // コンストラクタ
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->getCalledResourceHistory();
    }

    // デストラクタ
    public function __destruct()
    {
        // TwitterAPIの利用状況をDBに保存
        $this->postCalledResourceHistory();
    }

    // 一部リソースの利用状況を示す変数を初期化
    private function getCalledResourceHistory()
    {
        $this->calledCountFriendshipsCreateBefore15Minute   = 0;
        $this->calledCountFriendshipsCreateBefore24Hour     = 0;
        $this->calledCountFriendshipsCreateBefore24HourApp  = 0;
        $this->calledCountFriendshipsCreateNow              = 0;
        $this->calledCountFriendshipsDestroyBefore15Minute  = 0;
        $this->calledCountFriendshipsDestroyBefore24Hour    = 0;
        $this->calledCountFriendshipsDestroyNow             = 0;
        $this->calledCountStatusesUpdateBefore3HourApp      = 0;
        $this->calledCountStatusesUpdateNow                 = 0;
        $this->calledCountFavoritesCreateBefore24HourApp    = 0;
        $this->calledCountFavoritesCreateNow                = 0;
        $this->setCalledResourceSountBefore15Minute();
        $this->setCalledResourceSountBefore3Hour();
        $this->setCalledResourceSountBefore24Hour();
    }

    // リソースの利用状況をDBに保存
    private function postCalledResourceHistory()
    {
        if ($this->calledCountFriendshipsCreateNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FRIENDSHIPS_CREATE, 'count' => $this->calledCountFriendshipsCreateNow]))->save();
        }
        if ($this->calledCountFriendshipsDestroyNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FRIENDSHIPS_DESTROY, 'count' => $this->calledCountFriendshipsDestroyNow]))->save();
        }
        if ($this->calledCountStatusesUpdateNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::STATUSES_UPDATE, 'count' => $this->calledCountStatusesUpdateNow]))->save();
        }
        if ($this->calledCountFavoritesCreateNow > 0) {
            (new TwitterapiCalledLog(['user_id' => $this->user_id, 'resource_name' => self::FAVORITES_CREATE, 'count' => $this->calledCountFavoritesCreateNow]))->save();
        }
    }

    // 一部リソースの利用状況をDBから取得、フィールド変数に格納
    private function setCalledResourceSountBefore15Minute()
    {
        $now = new Carbon();
        // 過去１５分間の呼び出し履歴(アカウントごと)
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('user_id', '=', $this->user_id)
        ->where('created_at', '>', $now->subMinute(15))
        ->groupBy('resource_name')
        ->get();

        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::FRIENDSHIPS_CREATE:
                    $this->calledCountFriendshipsCreateBefore15Minute = $val->count;
                    break;
                case self::FRIENDSHIPS_DESTROY:
                    $this->calledCountFriendshipsDestroyBefore15Minute = $val->count;
                    break;
            }
        }
    }

    // 一部リソースの利用状況をDBから取得、フィールド変数に格納
    private function setCalledResourceSountBefore3Hour()
    {
        $now = new Carbon();
        // 過去３時間の呼び出し履歴(アプリ全体)
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('created_at', '>', $now->subHour(3))
        ->groupBy('resource_name')
        ->get();

        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::STATUSES_UPDATE:
                    $this->calledCountStatusesUpdateBefore3HourApp = $val->count;
                    break;
                default:
                    break;
            }
        }
    }

    // 一部リソースの利用状況をDBから取得、フィールド変数に格納
    private function setCalledResourceSountBefore24Hour()
    {
        // 過去２４時間の呼出履歴（アカウントごと）
        $now = new Carbon();
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('user_id', '=', $this->user_id)
        ->where('created_at', '>', $now->subHour(24))
        ->groupBy('resource_name')
        ->get();

        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::FRIENDSHIPS_CREATE:
                    $this->calledCountFriendshipsCreateBefore24Hour = $val->count;
                    break;
                case self::FRIENDSHIPS_DESTROY:
                    $this->calledCountFriendshipsDestroyBefore24Hour = $val->count;
                    break;
            }
        }

        // 過去２４時間の呼出履歴（アプリケーション全体）
        $now = new Carbon();
        $calledLog = DB::table('twitterapi_called_logs')
        ->select(DB::raw('SUM(count) as count, resource_name'))
        ->where('created_at', '>', $now->subHour(24))
        ->groupBy('resource_name')
        ->get();
        foreach ($calledLog as $val) {
            switch ($val->resource_name) {
                case self::FRIENDSHIPS_CREATE:
                    $this->calledCountFriendshipsCreateBefore24HourApp = $val->count;
                    break;
                case self::FAVORITES_CREATE:
                    $this->calledCountFavoritesCreateBefore24HourApp = $val->count;
                    break;
            }
        }
    }

    // リソース回数制限判定用
    public function check(string $resourceName)
    {
        if (!in_array($resourceName, $this::MANAGE_LIMIT_RESOURCE)) {
            return;
        }

        if ($resourceName === self::FRIENDSHIPS_CREATE) {
            // 24時間制限（アプリ全体）
            if ($this->calledCountFriendshipsCreateBefore24HourApp + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR_APP) {
                logger()->debug('API利用制限：firndships/createの２４時間制限（アプリ全体）'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            // １５分制限(アカウントごと)
            if ($this->calledCountFriendshipsCreateBefore15Minute + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_15MINUTE_ACCOUNT) {
                logger()->debug('API利用制限：firndships/createの１５分制限'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            // ２４時間制限(アカウントごと)
            if ($this->calledCountFriendshipsCreateBefore24Hour + $this->calledCountFriendshipsCreateNow >= $this::FRIENDSHIPS_CREATE_LIMIT_PER_24HOUR_ACCOUNT) {
                logger()->debug('API利用制限：firndships/createの２４時間制限'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            $this->calledCountFriendshipsCreateNow++;
        }

        if ($resourceName === self::FRIENDSHIPS_DESTROY) {
            // ２４時間制限(アカウントごと)
            if ($this->calledCountFriendshipsDestroyBefore24Hour + $this->calledCountFriendshipsDestroyNow >= $this::FRIENDHSIPS_DESTROY_LIMIT_PER_24HOUR_ACCOUNT) {
                logger()->debug('API利用制限：firndships/destroyの２４時間制限'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            $this->calledCountFriendshipsDestroyNow++;
        }

        if ($resourceName === self::STATUSES_UPDATE) {
            if ($this->calledCountStatusesUpdateBefore3HourApp + $this->calledCountStatusesUpdateNow >= $this::STATUSES_UPDATE_LIMIT_PER_3HOUR_APP) {
                logger()->debug('API利用制限：statuses/updateの３時間制限（アプリ全体）'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            $this->calledCountStatusesUpdateNow++;
        }

        if ($resourceName === self::FAVORITES_CREATE) {
            if ($this->calledCountFavoritesCreateBefore24HourApp + $this->calledCountFavoritesCreateNow >= $this::FAVORITES_CREATE_LIMIT_PER_24HOUR_APP) {
                logger()->debug('API利用制限：favorites/updateの３時間制限（アプリ全体）'." ".$this->user_id);
                throw new TwitterRestrictionException();
            }
            $this->calledCountFavoritesCreateNow++;
        }
    }
}
