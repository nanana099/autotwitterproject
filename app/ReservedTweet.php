<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReservedTweet extends Model
{
    protected $fillable = [
        'content',
        'submit_date',
        'account_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
      ];
}
