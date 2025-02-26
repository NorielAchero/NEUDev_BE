<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id('testCaseID');
            $table->unsignedBigInteger('questionID'); // Link to the question
            $table->text('inputData')->nullable(); // Input provided to the student's code
            $table->text('expectedOutput'); // Expected output for correctness check
            // $table->timestamps();

            $table->foreign('questionID')->references('questionID')->on('questions')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('test_cases');
    }
};