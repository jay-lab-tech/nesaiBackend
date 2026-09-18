<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Alumni extends Model { protected $guarded = []; public function major() { return $this->belongsTo(Major::class); } }
