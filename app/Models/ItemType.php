<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    use HasFactory;

    protected $table = 'item_types'; // Explicit table name
    protected $primaryKey = 'itemTypeID'; // Custom primary key
    public $timestamps = false;
    protected $fillable = [
        'itemTypeName',
    ];
}