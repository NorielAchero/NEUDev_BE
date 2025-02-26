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
        // ✅ Create the Questions Table
        Schema::create('questions', function (Blueprint $table) {
            $table->id('questionID'); // Primary Key
            $table->unsignedBigInteger('itemTypeID'); // Foreign Key for Item Type
            $table->string('questionName'); // Question Title
            $table->text('questionDesc'); // Question Description
            $table->enum('difficulty', ['Beginner', 'Intermediate', 'Advanced']);
            $table->timestamps();

            // ✅ Foreign Key Constraint
            $table->foreign('itemTypeID')->references('itemTypeID')->on('item_types')->onDelete('cascade');
        });

        // ✅ Create the Question Programming Languages Pivot Table
        Schema::create('question_programming_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionID');
            $table->unsignedBigInteger('progLangID');

            // ✅ Foreign Key Constraints
            $table->foreign('questionID')->references('questionID')->on('questions')->onDelete('cascade');
            $table->foreign('progLangID')->references('progLangID')->on('programming_languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_programming_languages'); // ✅ Drop pivot table first
        Schema::dropIfExists('questions'); // ✅ Drop Questions Table
    }
};