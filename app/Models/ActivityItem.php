<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityItem extends Model
{
    use HasFactory;

    protected $table = 'activity_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'actID',
        'itemID',          // was questionID
        'itemTypeID',
        'actItemPoints',   // was actQuestionPoints
    ];

    /**
     * Get the parent activity.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'actID', 'actID');
    }

    /**
     * Get the item details.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID')
                    ->with('testCases', 'itemType');
    }

    /**
     * Get the item type.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID', 'itemTypeID');
    }
}