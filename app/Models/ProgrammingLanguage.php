<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammingLanguage extends Model
{
    use HasFactory;

    protected $table = 'programming_languages'; // Explicit table name
    protected $primaryKey = 'progLangID'; // Custom primary key
    public $timestamps = false; // no need timestamps

    protected $fillable = [
        'name',
    ];

    /**
     * Get all activities that use this programming language.
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'progLangID', 'progLangID');
    }
}