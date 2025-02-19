<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'students'; // Explicit table name
    protected $primaryKey = 'studentID'; // Custom primary key
    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'student_num',
        'program',
        'profileImage',
        'coverImage',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function classes()
    {
        return $this->belongsToMany(Classroom::class, 'class_student', 'studentID', 'classID');
    }
}