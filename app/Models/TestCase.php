<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCase extends Model {
    use HasFactory;

    protected $table = 'test_cases';

    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'questionID',
        'inputData',
        'expectedOutput'
    ];

    public function question() {
        return $this->belongsTo(Question::class, 'questionID');
    }
}