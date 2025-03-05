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
        // Create the Items Table (renamed from "questions")
        Schema::create('items', function (Blueprint $table) {
            $table->id('itemID');                       // Primary Key
            $table->unsignedBigInteger('itemTypeID');    // Foreign Key for Item Type
            $table->unsignedBigInteger('teacherID')->nullable();
            
            $table->string('itemName');                  // Title
            $table->text('itemDesc');                    // Description
            $table->enum('itemDifficulty', ['Beginner', 'Intermediate', 'Advanced']);
            $table->integer('itemPoints');
            
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('itemTypeID')
                  ->references('itemTypeID')
                  ->on('item_types')
                  ->onDelete('cascade');

            $table->foreign('teacherID')
                  ->references('teacherID')
                  ->on('teachers')
                  ->onDelete('cascade');
        });

        // Create the Item Programming Languages Pivot Table (renamed from "question_programming_languages")
        Schema::create('item_programming_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itemID');
            $table->unsignedBigInteger('progLangID');

            // Foreign Key Constraints
            $table->foreign('itemID')
                  ->references('itemID')
                  ->on('items')
                  ->onDelete('cascade');

            $table->foreign('progLangID')
                  ->references('progLangID')
                  ->on('programming_languages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivot table first
        Schema::dropIfExists('item_programming_languages');
        // Then drop the Items table
        Schema::dropIfExists('items');
    }
};