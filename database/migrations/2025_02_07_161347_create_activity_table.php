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
        Schema::create('activity', function (Blueprint $table) {
            $table->id('actID');
            $table->string('classID');
            $table->string('teacherID');
            $table->string('questionID');
            $table->string('assessmentID');
            $table->string('actTitle');
            $table->string('actDesc');
            $table->string('difficulty');
            $table->string('startDate');
            $table->string('endDate');
            $table->string('progLang');

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity');
    }
};
