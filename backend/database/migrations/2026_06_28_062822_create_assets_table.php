<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relasi ke lab
            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->unique();        // kode aset, misal: CAM-001
            $table->string('category');              // dari AssetCategory enum
            $table->string('brand')->nullable();     // merk
            $table->string('model')->nullable();     // tipe/model
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->default('available'); // dari AssetStatus enum
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->json('specifications')->nullable(); // spesifikasi teknis
            $table->string('image')->nullable();
            $table->boolean('is_rentable')->default(true); // bisa disewa customer?
            $table->decimal('rental_price', 12, 2)->nullable(); // harga sewa per sesi

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('lab_id');
            $table->index('category');
            $table->index('status');
            $table->index('is_rentable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};