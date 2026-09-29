<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_item_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_item_id')
                  ->constrained('booking_items')
                  ->cascadeOnDelete();

            // nullable + nullOnDelete: kalau opsi aslinya dihapus admin,
            // histori booking lama tetap utuh lewat kolom snapshot di bawah
            $table->foreignId('service_option_id')
                  ->nullable()
                  ->constrained('service_options')
                  ->nullOnDelete();

            $table->string('name_snapshot');
            $table->string('price_type_snapshot');
            $table->decimal('price_snapshot', 12, 2);
            $table->integer('extra_minutes_snapshot')->nullable();
            $table->decimal('subtotal', 12, 2); // harga final opsi ini utk booking ini (udah dikali jam kalau per_hour)

            $table->timestamps();

            $table->index('booking_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_options');
    }
};