<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);           // harga package
            $table->decimal('discount', 5, 2)->default(0); // diskon dalam persen
            $table->integer('duration')->nullable();    // total durasi dalam menit
            $table->json('includes')->nullable();       // deskripsi apa saja yang termasuk
            $table->json('addons')->nullable();         // opsi tambahan
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false); // bisa dikustomisasi customer?

            $table->timestamps();
            $table->softDeletes();

            $table->index('lab_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};