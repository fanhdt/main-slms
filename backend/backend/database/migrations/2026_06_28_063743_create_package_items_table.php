<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')
                  ->constrained('packages')
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_items');
    }
};