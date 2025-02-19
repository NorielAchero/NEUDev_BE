<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Authenticatable
{
	use HasFactory, Notifiable, HasApiTokens;

	protected $table = 'teachers'; // Explicit table name
	protected $primaryKey = 'teacherID'; // Explicitly set the primary key
	public $timestamps = false; // Disable timestamps

	protected $fillable = [
		'firstname',
		'lastname',
		'email',
		'password',
		'profileImage',
        'coverImage'
	];

	protected $hidden = [
		'password'
	];

	protected function casts(): array
	{
		return [
			'password' => 'hashed'
		];
	}

	    /**
     * Get the class created by the teacher.
     */
    public function class()
    {
        return $this->hasMany(Classroom::class, 'teacherID', 'teacherID');
    }
}
