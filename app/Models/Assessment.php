<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'assessments';
    protected $primaryKey = 'assessmentID';
    public $timestamps = true;

    protected $fillable = [
        'actID',
        'itemID',
        'itemTypeID',
        'testCases',
        'submittedCode',
        'result',
        'executionTime',
        'progLang',
        'extraData'
    ];

    /**
     * Relationship to the activity.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'actID', 'actID');
    }

    /**
     * Relationship to the item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }

    /**
     * Relationship to the item type.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID', 'itemTypeID');
    }
}