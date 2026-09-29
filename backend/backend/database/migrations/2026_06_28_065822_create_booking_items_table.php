<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            
            // Bisa berupa layanan tunggal atau bundling paket
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            
            $table->integer('quantity')->default(1);
            $table->decimal('price', 12, 2); // Harga satuan saat dibooking
            $table->decimal('subtotal', 12, 2); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};