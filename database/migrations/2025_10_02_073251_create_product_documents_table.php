<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('document_type', ['fda_certificate','product_label','other']);
            $table->string('file_path', 255);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_documents');
    }
};
