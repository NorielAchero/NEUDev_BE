<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Needed for inserting default values

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('programming_languages', function (Blueprint $table) {
            $table->id('progLangID'); // Primary key
            $table->string('progLangName')->unique(); // Unique programming language names
            // $table->timestamps();
        });

        // Insert default programming languages
        DB::table('programming_languages')->insert([
            ['progLangName' => 'Java'],
            ['progLangName' => 'C#'],
            ['progLangName' => 'Python'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programming_languages');
    }
};