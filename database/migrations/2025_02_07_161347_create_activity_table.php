<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ Create the Activities Table
        Schema::create('activities', function (Blueprint $table) {
            $table->id('actID');
            // Change classID type to unsignedInteger to match the classes table custom primary key
            $table->unsignedInteger('classID'); // Foreign key to classes
            $table->unsignedBigInteger('teacherID'); // Foreign key to teachers
            $table->string('actTitle');
            $table->text('actDesc'); // Long description
            $table->enum('actDifficulty', ['Beginner', 'Intermediate', 'Advanced']);
            $table->string('actDuration', 8)->nullable();
            $table->dateTime('openDate');
            $table->dateTime('closeDate');
            $table->integer('maxPoints')->default(100); // Maximum points for activity
            $table->float('classAvgScore')->nullable(); // Class average score
            $table->float('highestScore')->nullable(); // Highest score in class
            $table->boolean('examMode')->default(false); // Tracks if tab/window switches are recorded
            $table->boolean('randomizedItems')->default(false); // Shuffles questions for each student
            $table->boolean('disableReviewing')->default(false); // Prevents students from reviewing activity after completion
            $table->boolean('hideLeaderboard')->default(false); // Hides leaderboard for this activity
            $table->boolean('delayGrading')->default(false); // Requires manual grading
            $table->timestamp('completed_at')->nullable(); // ✅ Track when the activity is completed

            // ✅ Foreign Key Constraints
            $table->foreign('classID')->references('classID')->on('classes')->onDelete('cascade');
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');

            $table->timestamps(); // ✅ Keeps track of created_at & updated_at
        });

        // ✅ Create the Activity Questions Table
        Schema::create('activity_questions', function (Blueprint $table) {
            $table->id('id');
            // Make sure actID type matches that of activities.actID
            $table->unsignedBigInteger('actID');
            $table->unsignedBigInteger('questionID'); 
            $table->unsignedBigInteger('itemTypeID');
            
            // Add a actQuestionPoints column with no default; teacher must supply a value.
            $table->integer('actQuestionPoints');
            
            // Foreign Key Constraints...
            $table->foreign('actID')->references('actID')->on('activities')->onDelete('cascade');
            $table->foreign('questionID')->references('questionID')->on('questions')->onDelete('cascade');
            $table->foreign('itemTypeID')->references('itemTypeID')->on('item_types')->onDelete('cascade');
        });
        
        // ✅ Create the Activity Programming Languages Table (Pivot Table)
        Schema::create('activity_programming_languages', function (Blueprint $table) {
            $table->id();
            // actID type should match the activities table
            $table->unsignedBigInteger('actID');
            $table->unsignedBigInteger('progLangID');

            // ✅ Foreign Key Constraints
            $table->foreign('actID')->references('actID')->on('activities')->onDelete('cascade');
            $table->foreign('progLangID')->references('progLangID')->on('programming_languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ✅ Drop the tables in the correct order to avoid foreign key conflicts
        Schema::dropIfExists('activity_programming_languages'); // Remove pivot table first
        Schema::dropIfExists('activity_questions');
        Schema::dropIfExists('activities');
    }
};
