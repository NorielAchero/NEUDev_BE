<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions'; // Explicit table name
    protected $primaryKey = 'questionID'; // Custom primary key
    public $timestamps = true;

    protected $fillable = [
        'itemTypeID',
        'questionName',
        'questionDesc',
        'questionDifficulty',
        'questionPoints',
    ];

    /**
     * Get the item type associated with this question.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID');
    }

    /**
     * Get the programming languages that this question supports.
     */
    public function programmingLanguages()
    {
        return $this->belongsToMany(
            ProgrammingLanguage::class, 
            'question_programming_languages', 
            'questionID', 
            'progLangID'
        );
    }

    /**
     * Get all test cases linked to this question.
     */
    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'questionID');
    }
}