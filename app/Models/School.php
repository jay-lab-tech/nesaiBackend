<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class School extends Model { protected $guarded = []; protected function casts(): array { return ['social_links' => 'array']; } }
