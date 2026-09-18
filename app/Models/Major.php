<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Major extends Model { protected $guarded = []; public function subjects() { return $this->hasMany(MajorSubject::class); } public function careers() { return $this->hasMany(Career::class); } public function alumni() { return $this->hasMany(Alumni::class); } }
