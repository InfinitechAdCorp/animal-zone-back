<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->after('contact_number');
            $table->string('province', 100)->nullable()->after('region');
            $table->string('city', 100)->nullable()->after('province');
            $table->string('barangay', 100)->nullable()->after('city');
            $table->string('street_address', 255)->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['region', 'province', 'city', 'barangay', 'street_address']);
        });
    }
};
