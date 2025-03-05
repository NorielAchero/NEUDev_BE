<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySubmission extends Model
{
    use HasFactory;

    protected $table = 'activity_submissions';
    protected $primaryKey = 'submissionID';
    public $timestamps = true;

    protected $fillable = [
        'actID',
        'studentID',
        'itemID',        // was questionID
        'codeSubmission',
        'score',
        'rank',
        'timeSpent',
        'submitted_at',
    ];

    /**
     * Get the associated activity.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'actID', 'actID');
    }

    /**
     * Get the student who made the submission.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID', 'studentID');
    }

    /**
     * Get the item (formerly question) linked to this submission.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }
}