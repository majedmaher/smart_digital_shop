<?php

namespace App\Traits;

trait HidesTimestamps
{
    protected static function bootHidesTimestamps()
    {
        static::retrieved(function ($model) {
            $defaultHidden = ['created_at', 'updated_at', 'deleted_at'];

            // merge بدل ما نعيد تعريف $hidden
            $model->setHidden(array_unique(array_merge($model->getHidden(), $defaultHidden)));
        });
    }
}
