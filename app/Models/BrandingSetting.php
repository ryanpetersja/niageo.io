<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandingSetting extends Model
{
    protected $fillable = [
        'company_name',
        'logo_path',
        'phone',
        'email',
        'website',
        'address',
        'footer_text',
    ];

    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'company_name' => config('app.name'),
        ]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        return null;
    }
}
