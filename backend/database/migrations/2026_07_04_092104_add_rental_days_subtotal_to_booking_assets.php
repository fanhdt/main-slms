<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_assets', function (Blueprint $table) {
            $table->unsignedInteger('rental_days')->default(1)->after('asset_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('rental_days');
        });
    }

    public function down(): void
    {
        Schema::table('booking_assets', function (Blueprint $table) {
            $table->dropColumn(['rental_days', 'subtotal']);
        });
    }
};