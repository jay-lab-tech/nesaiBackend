<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ppdb extends Model { protected $guarded = []; protected $table = 'ppdb'; protected function casts(): array { return ['requirements' => 'array', 'schedule' => 'array']; } }
