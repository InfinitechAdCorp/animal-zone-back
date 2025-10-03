<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // Default product categories
        DB::table('product_categories')->insert([
            ['name' => 'Pet Food'],
            ['name' => 'Treats & Snacks'],
            ['name' => 'Grooming & Hygiene'],
            ['name' => 'Toys & Accessories'],
            ['name' => 'Health & Wellness'],
            ['name' => 'Clothing & Costumes'],
            ['name' => 'Supplies & Equipment'],
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('product_categories');
    }
};
