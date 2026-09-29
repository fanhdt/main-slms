<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photographer_portfolios', function (Blueprint $table) {
            $table->foreignId('photographer_id')
                  ->nullable() // nullable dulu, biar data lama tidak error
                  ->after('lab_id')
                  ->constrained('photographers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('photographer_portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photographer_id');
        });
    }
};