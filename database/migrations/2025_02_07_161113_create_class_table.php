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
        Schema::create('class', function (Blueprint $table) {
            $table->id('classID');
            $table->string('className');
            $table->unsignedBigInteger('teacherID');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
        });

        // Create pivot table for many-to-many relationship
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classID');
            $table->unsignedBigInteger('studentID');

            $table->foreign('classID')->references('classID')->on('class')->onDelete('cascade');
            $table->foreign('studentID')->references('studentID')->on('students')->onDelete('cascade');

            $table->unique(['classID', 'studentID']); // Prevent duplicate entries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('class');
    }
};
