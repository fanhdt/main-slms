<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // UUID untuk identifier publik di API
            $table->uuid('uuid')->unique()->after('id');

            // Tambahan field profil
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');

            // Soft delete
            $table->softDeletes();

            // Index untuk performa
            $table->index('uuid');
            $table->index('is_active');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'phone', 'avatar', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
