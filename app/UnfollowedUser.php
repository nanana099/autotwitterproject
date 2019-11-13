<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnfollowedUser extends Model
{
    
    protected $fillable = ['user_id','account_id', ];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
      ];
    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
