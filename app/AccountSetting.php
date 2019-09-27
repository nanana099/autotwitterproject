<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    protected $fillable = [
        'keyword_follow',
        'keyword_favorite',
        'days_inactive_user',
        'days_unfollow_user',
        'num_max_unfollow_per_day',
        'num_user_start_unfollow',
        'bool_unfollow_inactive',
        'account_id',
        'target_accounts'
    ];
}
