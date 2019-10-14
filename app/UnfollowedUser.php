<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnfollowedUser extends Model
{
    
    protected $fillable = ['user_id','account_id', ];

    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
