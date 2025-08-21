<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Seo extends Model
{
    use HasTranslations;

    protected $guarded = [];
    public $translatable = ['title', 'description', 'keywords'];
}
