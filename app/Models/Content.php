<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
