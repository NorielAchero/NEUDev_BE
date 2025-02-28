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
        'actTitle',
        'actDesc',
        'actDifficulty',
        'actDuration',
        'openDate',
        'closeDate',
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
     * Get the programming languages used in this activity.
     */
    public function programmingLanguages()
    {
        return $this->belongsToMany(
            ProgrammingLanguage::class, 
            'activity_programming_languages', 
            'actID', 
            'progLangID'
        );
    }

    /**
     * Get all preset questions linked to this activity.
     */
    public function questions()
    {
        return $this->hasMany(ActivityQuestion::class, 'actID', 'actID')
                    ->with(['question' => function ($query) {
                        $query->with('testCases', 'itemType'); // ✅ Eager load test cases & item type
                    }]);
    }
}