<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->string('type');                    // dari ServiceType enum
            $table->text('description')->nullable();
            $table->string('pricing_type');            // dari PricingType enum
            $table->decimal('price', 12, 2);
            $table->integer('duration')->nullable();   // durasi dalam menit
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->json('includes')->nullable();      // apa saja yang termasuk
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('lab_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};