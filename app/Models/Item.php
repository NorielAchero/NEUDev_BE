<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items'; // Renamed from "questions"
    protected $primaryKey = 'itemID';
    public $timestamps = true;

    protected $fillable = [
        'itemTypeID',
        'teacherID',
        'itemName',         // was questionName
        'itemDesc',         // was questionDesc
        'itemDifficulty',   // was questionDifficulty
        'itemPoints',       // was questionPoints
    ];

    /**
     * Get the item type.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID', 'itemTypeID');
    }

    /**
     * Many-to-many relationship to programming languages.
     */
    public function programmingLanguages()
    {
        return $this->belongsToMany(
            ProgrammingLanguage::class,
            'item_programming_languages', // renamed pivot table
            'itemID',
            'progLangID'
        );
    }

    /**
     * Get the test cases associated with this item.
     */
    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'itemID', 'itemID');
    }

    /**
     * Get the teacher who created this item.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }
}