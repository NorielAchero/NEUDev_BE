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
            // $table->timestamps();

            // ✅ Foreign Key Constraint
            $table->foreign('itemTypeID')->references('itemTypeID')->on('item_types')->onDelete('cascade');
        });

        // ✅ Insert Default Questions for "Console App"
        DB::table('questions')->insert([
            [
                'questionName' => 'Array Problem',
                'questionDesc' => 'Write a function that manipulates arrays efficiently.',
                'difficulty' => 'Beginner',
                'itemTypeID' => 1
            ],
            [
                'questionName' => 'Tic-Tac-Toe',
                'questionDesc' => 'Implement a simple Tic-Tac-Toe game using console input.',
                'difficulty' => 'Intermediate',
                'itemTypeID' => 1
            ],
            [
                'questionName' => 'Christmas Tree Loop',
                'questionDesc' => 'Implement a simple Christmas Tree Loop using console input.',
                'difficulty' => 'Advanced',
                'itemTypeID' => 1
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions'); // ✅ Drop Table
    }
};