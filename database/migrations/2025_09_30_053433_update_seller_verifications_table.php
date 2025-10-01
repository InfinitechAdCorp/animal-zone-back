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
        Schema::table('seller_verifications', function (Blueprint $table) {
            // Add new columns for multiple business permits
            $table->json('business_permit_types')->nullable()->after('company_name');
            $table->string('dti_sec')->nullable()->after('business_permit_types');
            $table->string('mayors_permit')->nullable()->after('dti_sec');
            $table->string('bir_certificate')->nullable()->after('mayors_permit');
            
            // Drop old single business_permit column
            $table->dropColumn('business_permit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_verifications', function (Blueprint $table) {
            // Restore old column
            $table->string('business_permit')->nullable();
            
            // Remove new columns
            $table->dropColumn([
                'business_permit_types',
                'dti_sec',
                'mayors_permit',
                'bir_certificate'
            ]);
        });
    }
};