<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    // protected $guarded = ['id'];
    protected $fillable = ['twitter_user_id','access_token', 'user_id', 'screen_name', 'image_url'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
      ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function operationStatus()
    {
        return $this->hasOne('App\OperationStatus');
    }

    public function accountSetting()
    {
        return $this->hasOne('App\AccountSetting');
    }

    public function reservedTweets()
    {
        return $this->hasMany('App\ReservedTweet');
    }

    public function followedUsers()
    {
        return $this->hasMany('App\FollowedUser');
    }

    public function unfollowedUsers()
    {
        return $this->hasMany('App\UnfollowedUser');
    }
}
