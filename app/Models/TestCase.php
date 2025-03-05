<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    use HasFactory;

    protected $table = 'test_cases';
    protected $primaryKey = 'testCaseID';
    public $timestamps = false;

    protected $fillable = [
        'itemID',         // was questionID
        'inputData',
        'expectedOutput',
        'testCasePoints',
        'isHidden',       // NEW for hiding from students
    ];

    /**
     * Relationship back to the parent Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }
}