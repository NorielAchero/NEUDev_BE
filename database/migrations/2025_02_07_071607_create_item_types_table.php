<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table) {
            $table->id('itemTypeID');
            $table->string('itemTypeName')->unique(); // Example: "Console App", "Web App"
            // $table->timestamps();
        });

        // ✅ Insert default item type: "Console App"
        DB::table('item_types')->insert([
            ['itemTypeName' => 'Console App'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_types');
    }
};