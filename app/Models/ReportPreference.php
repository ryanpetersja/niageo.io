<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportPreference extends Model
{
    protected $fillable = [
        'rules',
        'last_distilled_at',
    ];

    protected $casts = [
        'rules' => 'array',
        'last_distilled_at' => 'datetime',
    ];

    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'rules' => [],
        ]);
    }
}
