<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seller_payment_methods', function (Blueprint $table) {
            $table->string('method')->after('seller_id');   // e.g., gcash, paymaya, etc.
            $table->boolean('enabled')->default(false)->after('method');
            $table->string('qr_path')->nullable()->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('seller_payment_methods', function (Blueprint $table) {
            $table->dropColumn(['method', 'enabled', 'qr_path']);
        });
    }
};
