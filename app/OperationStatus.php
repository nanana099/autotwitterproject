<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OperationStatus extends Model
{
    protected $fillable = [
        'is_follow',
        'is_unfollow',
        'is_favorite',
        'account_id',
    ];
}
