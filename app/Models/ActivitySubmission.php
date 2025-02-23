<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySubmission extends Model
{
    use HasFactory;

    protected $table = 'activity_submissions'; // Explicit table name
    protected $primaryKey = 'submissionID'; // Custom primary key
    public $timestamps = true; // Keep timestamps

    protected $fillable = [
        'actID',
        'studentID',
        'codeSubmission',
        'submitted_at',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'actID', 'actID');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID', 'studentID');
    }
}