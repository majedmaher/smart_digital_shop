<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    protected $guarded = [];

    use HasTranslations, SoftDeletes;
    public array $translatable = ['name'];
}
