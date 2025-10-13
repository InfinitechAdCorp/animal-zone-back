<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('gcash_qr')->nullable()->after('postal_code');
        $table->string('paymaya_qr')->nullable()->after('gcash_qr');
        $table->string('bpi_qr')->nullable()->after('paymaya_qr');
        $table->string('bdo_qr')->nullable()->after('bpi_qr');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['gcash_qr', 'paymaya_qr', 'bpi_qr', 'bdo_qr']);
    });
}

};
