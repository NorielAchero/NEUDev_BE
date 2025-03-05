<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id('testCaseID');
            $table->unsignedBigInteger('itemID');   // was "questionID"
            
            $table->text('inputData')->nullable();
            $table->text('expectedOutput');
            
            $table->integer('testCasePoints');
            
            // NEW: Hide from students if true
            $table->boolean('isHidden')->default(false);

            // Foreign key constraint
            $table->foreign('itemID')
                  ->references('itemID')
                  ->on('items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('test_cases');
    }
};