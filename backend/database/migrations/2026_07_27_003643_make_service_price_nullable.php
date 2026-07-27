<?php
// database/migrations/2026_07_27_000000_make_service_price_nullable.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->string('pricing_type')->nullable()->change();
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable(false)->change();
            $table->string('pricing_type')->nullable(false)->change();
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable(false)->change();
        });
    }
};