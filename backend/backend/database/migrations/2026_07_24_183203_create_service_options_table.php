<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            $table->string('name');                 // "Retouch", "Remove Background"
            $table->text('description')->nullable();
            $table->string('price_type');            // 'flat' | 'per_hour' | 'per_photo'
            $table->decimal('price', 12, 2);
            $table->integer('extra_minutes')->nullable(); // nambah estimasi durasi kalau ada
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('service_id');
            $table->index(['service_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_options');
    }
};