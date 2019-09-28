<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    // protected $guarded = ['id'];
    protected $fillable = ['id','access_token', 'user_id', 'screen_name', 'image_url'];

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
}
