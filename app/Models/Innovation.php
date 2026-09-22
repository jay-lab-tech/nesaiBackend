<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Innovation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'has_haki' => 'boolean',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }
}
