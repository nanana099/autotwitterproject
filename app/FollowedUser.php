<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FollowedUser extends Model
{
    protected $fillable = ['user_id','followed_at', 'account_id', ];

    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
