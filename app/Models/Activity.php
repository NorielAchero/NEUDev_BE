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
        'maxPoints',
        'classAvgScore',
        'highestScore',
        'examMode',
        'randomizedItems',
        'disableReviewing',
        'hideLeaderboard',
        'delayGrading',
        'completed_at'
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
     * Get the programming language associated with this activity.
     */
    public function programmingLanguage()
    {
        return $this->belongsTo(ProgrammingLanguage::class, 'progLangID', 'progLangID');
    }

    /**
     * Get all preset questions linked to this activity.
     */
    public function questions()
    {
        return $this->hasMany(ActivityQuestion::class, 'actID', 'actID')->with(['question', 'itemType']);
    }
}