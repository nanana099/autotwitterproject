<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OperationStatus extends Model
{
    protected $fillable = [
        'is_follow',
        'is_unfollow',
        'is_favorite',
        'account_id',
        'is_flozen',
        'follow_stopped_at',
        'unfollow_stopped_at',
        'favorite_stopped_at',
        'tweet_stopped_at',
        'following_target_account',
        'following_target_account_cursor',
        'unfollowing_target_cursor',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_follow' => 'boolean',
        'is_unfollow' => 'boolean',
        'is_favorite' => 'boolean',
        'is_flozen' => 'boolean',
        'account_id' => 'integer',
      ];
}
