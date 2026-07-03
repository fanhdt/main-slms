<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Branding
            $table->string('logo')->nullable();
            $table->string('primary_color', 7)->nullable();    // hex: #RRGGBB
            $table->string('secondary_color', 7)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('favicon')->nullable();

            // Metadata JSON
            $table->json('contact')->nullable();   // {email, phone, address, maps_url}
            $table->json('settings')->nullable();  // konfigurasi per lab

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};
