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
        // ✅ Rename "class" to "classes"
        Schema::create('classes', function (Blueprint $table) {
            $table->id('classID');
            $table->string('className');
            $table->unsignedBigInteger('teacherID');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
        });

        // ✅ Rename "class_student" foreign key references
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classID');
            $table->unsignedBigInteger('studentID');

            // ✅ Change reference from "class" to "classes"
            $table->foreign('classID')->references('classID')->on('classes')->onDelete('cascade');
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
        Schema::dropIfExists('classes'); // ✅ Drop "classes" instead of "class"
    }
};