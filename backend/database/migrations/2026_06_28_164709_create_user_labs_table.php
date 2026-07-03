<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_labs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            // Role spesifik user di lab ini
            // Bisa berbeda dengan role global user
            $table->string('role')->nullable();

            $table->timestamps();

            // Satu user tidak bisa assign dua kali ke lab yang sama
            $table->unique(['user_id', 'lab_id']);

            $table->index('user_id');
            $table->index('lab_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_labs');
    }
};