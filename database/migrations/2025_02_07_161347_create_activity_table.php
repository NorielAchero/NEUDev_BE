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
        Schema::create('activities', function (Blueprint $table) {
            $table->id('actID');
            $table->unsignedBigInteger('classID'); // Foreign key to classes
            $table->unsignedBigInteger('teacherID'); // Foreign key to teachers
            $table->unsignedBigInteger('progLangID'); // Foreign key reference
            $table->string('actTitle');
            $table->text('actDesc'); // Use text for long descriptions
            $table->enum('difficulty', ['Easy', 'Medium', 'Hard']);
            $table->dateTime('startDate');
            $table->dateTime('endDate');

            // ✅ Correct Foreign Key Constraints
            $table->foreign('classID')->references('classID')->on('classes')->onDelete('cascade');
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
            $table->foreign('progLangID')->references('progLangID')->on('programming_languages')->onDelete('cascade');

            $table->timestamps(); // ✅ Keeps track of created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};