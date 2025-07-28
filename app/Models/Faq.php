<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    protected $guarded = [];
    public array $translatable = ['question', 'answer'];

    protected $casts = [
        'question' => 'array',
        'answer' => 'array',
    ];
}
