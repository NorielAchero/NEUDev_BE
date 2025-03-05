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
        Schema::create('activity_submissions', function (Blueprint $table) {
            $table->id('submissionID');
            $table->unsignedBigInteger('actID');
            $table->unsignedBigInteger('studentID');
            $table->unsignedBigInteger('itemID');   // was "questionID"
            
            $table->text('codeSubmission');
            $table->integer('score')->nullable();
            $table->integer('rank')->nullable();
            $table->integer('timeSpent')->nullable();
            $table->dateTime('submitted_at')->nullable();

            // Foreign keys
            $table->foreign('actID')
                  ->references('actID')
                  ->on('activities')
                  ->onDelete('cascade');

            $table->foreign('studentID')
                  ->references('studentID')
                  ->on('students')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_submissions');
    }
};