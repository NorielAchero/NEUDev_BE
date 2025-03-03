<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    // Change the table name to "classes" to match Laravel conventions
    protected $table = 'classes';
    protected $primaryKey = 'classID';
    public $timestamps = true;

    // Disable auto-incrementing since we're using a custom key
    public $incrementing = false;
    // Set the key type as integer (or string if you prefer)
    protected $keyType = 'integer';

    protected $fillable = [
        'className',
        'classSection',
        'teacherID',
    ];

    /**
     * Boot the model to assign a random 6-digit classID on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->classID)) {
                do {
                    // Generate a random number between 100000 and 999999
                    $randomID = random_int(100000, 999999);
                } while (self::where('classID', $randomID)->exists());

                $model->classID = $randomID;
            }
        });
    }

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
