<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterapiCalledLog extends Model
{
    //
    protected $fillable = ['user_id','resource_name', 'count'];

    protected $casts = [
        'id' => 'integer',
        'count' => 'integer',
      ];
}
