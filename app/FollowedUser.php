<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FollowedUser extends Model
{
    protected $fillable = ['user_id','followed_at', 'account_id', ];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
      ];

    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
