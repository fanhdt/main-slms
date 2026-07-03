<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('booking_code')->unique(); // Contoh: BK-20260628-XXXX
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            
            $table->string('status')->default('pending'); // Dari BookingStatus enum
            $table->string('payment_status')->default('unpaid'); // Dari PaymentStatus enum
            
            $table->decimal('total_price', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['lab_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};