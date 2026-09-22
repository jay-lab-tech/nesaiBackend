<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'social_links' => 'array',
        'stats_updated_at' => 'datetime',
    ];
}
