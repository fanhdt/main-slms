<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();

            $table->foreignId('asset_id')
                ->nullable()
                ->after('service_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->integer('duration_minutes')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn('duration_minutes');
            $table->foreignId('service_id')->nullable(false)->change();
        });
    }
};