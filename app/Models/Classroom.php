<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $table = 'classes'; 
    protected $primaryKey = 'classID'; 
    public $timestamps = true; 

    protected $fillable = [
        'className',
        'teacherID',
    ];

    /**
     * Get the teacher who created the class.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }

    /**
     * The students that belong to the class.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student', 'classID', 'studentID');
    }
}