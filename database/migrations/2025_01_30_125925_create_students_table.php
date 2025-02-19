<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
          $table->id('studentID');
          // $table->timestamps();
					$table->string('firstname');
					$table->string('lastname');
          $table->string('email')->unique();
          $table->string('student_num')->unique();// Enforce uniqueness
          $table->enum('program', ['BSCS', 'BSIT', 'BSEMC', 'BSIS']); // Restrict values
          $table->string('password');
          $table->string('profileImage')->nullable();
          $table->string('coverImage')->nullable();
        });

				Schema::create('student_password_reset_tokens', function(Blueprint $table) {
					$table->string('email')->primary();
					$table->string('token');
					$table->string('created_at');
				});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('student_password_reset_tokens');
    }
};