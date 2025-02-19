<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities'; // Explicitly define the table name
    protected $primaryKey = 'actID'; // Set the custom primary key
    public $timestamps = true; // Keep timestamps

    protected $fillable = [
        'classID',
        'teacherID',
        'progLangID',
        'actTitle',
        'actDesc',
        'difficulty',
        'startDate',
        'endDate',
    ];

    /**
     * Get the class associated with this activity.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classID', 'classID');
    }

    /**
     * Get the teacher who created this activity.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }

    /**
     * Get all student submissions for this activity.
     */
    public function submissions()
    {
        return $this->hasMany(ActivitySubmission::class, 'actID', 'actID');
    }

    /**
     * Get the programming language associated with this activity.
     */
    public function programmingLanguage()
    {
        return $this->belongsTo(ProgrammingLanguage::class, 'progLangID', 'progLangID');
    }
}