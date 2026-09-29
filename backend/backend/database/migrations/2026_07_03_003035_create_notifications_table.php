<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type');          // BookingCreated, PhotoPreviewUploaded, dll (nama event)
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable(); // payload tambahan: booking_uuid, photo_project_uuid, dll
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index('lab_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};