<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // Insert default categories
        DB::table('categories')->insert([
            ['name' => 'Dog'],
            ['name' => 'Cat'],
            ['name' => 'Bird'],
            ['name' => 'Fish'],
            ['name' => 'Rabbit'],
            ['name' => 'Hamster'],
            ['name' => 'Others'],
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('categories');
    }
};
