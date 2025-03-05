<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammingLanguage extends Model
{
    use HasFactory;

    protected $table = 'programming_languages'; // Explicit table name
    protected $primaryKey = 'progLangID'; // Custom primary key
    public $timestamps = false; // No need timestamps

    protected $fillable = [
        'progLangName',
    ];

    /**
     * Get all activities that use these programming languages.
     */
    public function activities()
    {
        return $this->belongsToMany(
            Activity::class, 
            'activity_programming_languages', 
            'progLangID', 
            'actID'
        );
    }

    /**
     * Get all items that support this programming language.
     */
    public function items()
    {
        return $this->belongsToMany(
            Item::class, 
            'item_programming_languages', 
            'progLangID', 
            'itemID'
        );
    }
}