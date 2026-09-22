<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniTrackingStat extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'employed_percent' => 'decimal:2',
        'entrepreneur_percent' => 'decimal:2',
        'college_percent' => 'decimal:2',
        'other_percent' => 'decimal:2',
    ];
}
