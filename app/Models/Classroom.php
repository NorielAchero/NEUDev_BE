<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    // ✅ Change the table name to "classes" to match Laravel conventions
    protected $table = 'classes'; 
    protected $primaryKey = 'classID'; 
    public $timestamps = true; 

    protected $fillable = [
        'className',
        'classSection',
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

    /**
     * Get the activities for this class.
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'classID', 'classID');
    }
}