<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photographer_portfolios', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            // Sengaja string biasa, bukan FK ke users — fotografer belum tentu
            // punya akun login. Bisa diupgrade jadi FK (user_id nullable) nanti
            // tanpa migrasi ulang skema besar.
            $table->string('photographer_name');

            $table->string('image');
            $table->string('disk')->default('s3');
            $table->string('caption')->nullable();
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index('lab_id');
            $table->index(['lab_id', 'photographer_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photographer_portfolios');
    }
};