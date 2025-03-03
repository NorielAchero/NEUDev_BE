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
        // Create the "classes" table with a custom primary key (classID)
        Schema::create('classes', function (Blueprint $table) {
            // Instead of auto-increment, define classID as an unsigned integer primary key
            $table->unsignedInteger('classID')->primary();
            $table->string('className');
            $table->string('classSection');
            $table->unsignedBigInteger('teacherID');
            $table->timestamps();

            // Foreign key constraint: teacherID references teachers table
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
        });

        // Create the pivot table for class-student relationships
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            // Use unsignedInteger for classID to match the classes table
            $table->unsignedInteger('classID');
            $table->unsignedBigInteger('studentID');

            // Foreign key constraints for class_student
            $table->foreign('classID')->references('classID')->on('classes')->onDelete('cascade');
            $table->foreign('studentID')->references('studentID')->on('students')->onDelete('cascade');

            // Prevent duplicate entries
            $table->unique(['classID', 'studentID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('classes');
    }
};
