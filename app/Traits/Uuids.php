<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Uuids
{
    // Overwrite boot function from Laravel
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    // Set $incrementing = false
    public function getIncrementing()
    {
        return false;
    }

    // Set $keyType = string
    public function getKeyType()
    {
        return 'string';
    }
}