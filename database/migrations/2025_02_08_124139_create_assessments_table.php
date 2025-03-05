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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id('assessmentID');
            // Link to the activity. Use unsignedBigInteger to match the activities table.
            $table->unsignedBigInteger('actID');
            // Optional link to a specific item, if needed
            $table->unsignedBigInteger('itemID')->nullable();
            // Optional link to the item type, providing extra flexibility
            $table->unsignedBigInteger('itemTypeID')->nullable();
            
            // Use text for fields that might be lengthy, and allow them to be nullable.
            $table->text('testCases')->nullable();
            $table->text('submittedCode')->nullable();
            $table->string('result')->nullable();
            $table->string('executionTime')->nullable();
            $table->string('progLang')->nullable();
            
            // Extra data stored as JSON for any item-specific details
            $table->json('extraData')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('actID')
                  ->references('actID')
                  ->on('activities')
                  ->onDelete('cascade');
            $table->foreign('itemID')
                  ->references('itemID')
                  ->on('items')
                  ->onDelete('cascade');
            $table->foreign('itemTypeID')
                  ->references('itemTypeID')
                  ->on('item_types')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};