<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $guarded = [];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_placeholder' => 'boolean',
        ];
    }
}
