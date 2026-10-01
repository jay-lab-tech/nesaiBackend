<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'summary',
        'description',
        'logo',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return asset('storage/' . ltrim($this->logo, '/'));
    }

    public function subjects()
    {
        return $this->hasMany(MajorSubject::class);
    }

    public function careers()
    {
        return $this->hasMany(Career::class);
    }

    public function alumni()
    {
        return $this->hasMany(Alumni::class);
    }

    public function innovations()
    {
        return $this->hasMany(Innovation::class);
    }

    public function admissionStats()
    {
        return $this->hasMany(AdmissionStat::class);
    }
}
