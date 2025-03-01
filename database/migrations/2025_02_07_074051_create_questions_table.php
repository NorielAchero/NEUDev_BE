<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the Questions Table
        Schema::create('questions', function (Blueprint $table) {
            $table->id('questionID'); // Primary Key
            $table->unsignedBigInteger('itemTypeID'); // Foreign Key for Item Type
            // New column to associate the question with a teacher.
            // When teacherID is NULL, this question is global (NEUDev).
            // When teacherID is set, it is a personal question created by that teacher.
            $table->unsignedBigInteger('teacherID')->nullable();
            $table->string('questionName'); // Question Title
            $table->text('questionDesc'); // Question Description
            $table->enum('questionDifficulty', ['Beginner', 'Intermediate', 'Advanced']);
            $table->integer('questionPoints');
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('itemTypeID')->references('itemTypeID')->on('item_types')->onDelete('cascade');
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
        });

        // Create the Question Programming Languages Pivot Table
        Schema::create('question_programming_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionID');
            $table->unsignedBigInteger('progLangID');

            // Foreign Key Constraints for pivot table
            $table->foreign('questionID')->references('questionID')->on('questions')->onDelete('cascade');
            $table->foreign('progLangID')->references('progLangID')->on('programming_languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_programming_languages'); // Drop pivot table first
        Schema::dropIfExists('questions'); // Then drop the Questions Table
    }
};