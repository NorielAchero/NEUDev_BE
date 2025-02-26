<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityQuestion extends Model
{
    use HasFactory;

    protected $table = 'activity_questions'; // Explicit table name
    protected $primaryKey = 'id'; // Custom primary key
    public $timestamps = false; // No timestamps needed

    protected $fillable = [
        'actID',
        'questionID',
        'itemTypeID',
    ];

    /**
     * Get the activity associated with this question.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'actID', 'actID');
    }

    /**
     * Get the question details.
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'questionID', 'questionID')
                    ->with('testCases'); // ✅ Eager load test cases for this question
    }
    

    /**
     * Get the item type for this question.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID', 'itemTypeID');
    }
}