// database/migrations/2026_07_16_000001_add_quantity_to_booking_assets_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_assets', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_assets', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};