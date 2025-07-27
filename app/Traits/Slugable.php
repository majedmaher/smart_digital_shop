<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Slugable
{
    public static function bootSlugable()
    {
        static::saving(function ($model) {
            self::generateUniqueSlug($model);
        });
        static::creating(function ($model) {
            self::generateUniqueSlug($model);
        });
        static::updating(function ($model) {
            self::generateUniqueSlug($model);
        });
    }

    protected static function generateUniqueSlug($model)
    {
        $field = isset($model->name) ? 'name' : 'title';

        // if (!empty($model->slug)) return;

        $slugs = [];
        $random = rand(100000, 999999);

        $translations = $model->getTranslations($field);

        foreach ($translations as $locale => $value) {
            if (empty($value)) continue;

            if ($locale === 'ar') {
                // حافظ على النص العربي كما هو، فقط استبدل المسافات بشرطة
                $slug = preg_replace('/\s+/u', '-', trim($value));
            } else {
                // اللغات الأخرى: استخدم Str::slug بشكل طبيعي
                $slug = Str::slug($value, '-', $locale);
            }

            $slugs[$locale] = "{$slug}-{$random}";
        }

        $model->setTranslations('slug', $slugs);
    }
}
