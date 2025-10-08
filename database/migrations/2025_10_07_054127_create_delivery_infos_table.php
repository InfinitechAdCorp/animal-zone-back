<?php

// database/migrations/xxxx_xx_xx_create_delivery_infos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_infos');
    }
};
