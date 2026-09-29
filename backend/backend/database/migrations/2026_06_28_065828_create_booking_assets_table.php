<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_assets', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            
            $table->string('status')->default('borrowed'); // borrowed, returned, damaged
            $table->text('return_notes')->nullable(); // Catatan kondisi saat dikembalikan
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_assets');
    }
};