<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZohoToken extends Model
{
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime'];
}
