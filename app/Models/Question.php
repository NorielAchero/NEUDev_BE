<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions'; // Explicit table name
    protected $primaryKey = 'questionID'; // Custom primary key
    public $timestamps = false;

    protected $fillable = [
        'itemTypeID',
        'questionName',
        'questionDesc',
        'difficulty',
    ];

    /**
     * Get the item type associated with this question.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'itemTypeID', 'itemTypeID');
    }
}