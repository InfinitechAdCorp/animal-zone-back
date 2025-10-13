<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('seller_payment_methods', function (Blueprint $table) {
        $table->unsignedBigInteger('seller_id')->after('id');

        // Optional: add foreign key relationship
        $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('seller_payment_methods', function (Blueprint $table) {
        $table->dropForeign(['seller_id']);
        $table->dropColumn('seller_id');
    });
}

};
