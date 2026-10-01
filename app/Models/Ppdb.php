<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ppdb extends Model
{
    protected $table = 'ppdb';
    protected $guarded = ['id'];

    protected $casts = [
        'requirements' => 'array',
        'schedule' => 'array',
        'is_active' => 'boolean',
    ];
}
