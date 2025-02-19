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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id('teacherID');
            // $table->timestamps();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('profileImage')->nullable();
            $table->string('coverImage')->nullable();
        });

        Schema::create('teacher_password_reset_tokens', function(Blueprint $table) {
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
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('teacher_password_reset_tokens');
    }
};
