<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    protected $guarded = ['id'];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }
}
