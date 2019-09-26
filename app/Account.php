<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    // protected $guarded = ['id'];
    protected $fillable = ['id','access_token', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
