<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities';
    protected $primaryKey = 'actID';
    public $timestamps = true;

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
     * Get the classroom associated with this activity.
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
     * Get the programming languages for this activity.
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
     * Get all items (previously "questions") linked to this activity.
     */
    public function items()
    {
        return $this->hasMany(ActivityItem::class, 'actID', 'actID')
                    ->with(['item' => function ($query) {
                        $query->with('testCases', 'itemType');
                    }]);
    }
}