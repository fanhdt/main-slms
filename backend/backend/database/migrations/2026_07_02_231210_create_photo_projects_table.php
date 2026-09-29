<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->cascadeOnDelete();

            $table->foreignId('lab_id')
                  ->constrained('labs')
                  ->cascadeOnDelete();

            $table->string('status')->default('pending');
            $table->integer('preview_count')->default(0);
            $table->integer('selection_count')->default(0);
            $table->integer('max_selection')->default(0);
            $table->text('notes')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('editor_note')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('booking_id');
            $table->index('lab_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_projects');
    }
};