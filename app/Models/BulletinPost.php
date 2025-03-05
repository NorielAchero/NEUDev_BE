<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulletinPost extends Model {
    use HasFactory;

    protected $fillable = ['classID', 'teacherID', 'title', 'message'];

    public function teacher() {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }

    public function classroom() {
        return $this->belongsTo(Classroom::class, 'classID', 'classID');
    }
}
