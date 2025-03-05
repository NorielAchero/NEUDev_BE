<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bulletin_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('classID'); // Foreign key to classes
            $table->unsignedBigInteger('teacherID'); // Foreign key to teachers
            $table->string('title');
            $table->text('message');
            $table->timestamps();

            // Define foreign keys
            $table->foreign('classID')->references('classID')->on('classes')->onDelete('cascade');
            $table->foreign('teacherID')->references('teacherID')->on('teachers')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bulletin_posts');
    }
};
