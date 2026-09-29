<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();

            $table->string('type'); // daily | weekly
            $table->date('period_start');
            $table->date('period_end');

            $table->integer('total_bookings')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->json('summary')->nullable(); // breakdown by status/type/payment

            $table->string('excel_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('disk')->default('s3');

            $table->timestamps();

            $table->index(['lab_id', 'type']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};