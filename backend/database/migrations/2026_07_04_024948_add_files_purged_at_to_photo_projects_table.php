<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_projects', function (Blueprint $table) {
            $table->timestamp('files_purged_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('photo_projects', function (Blueprint $table) {
            $table->dropColumn('files_purged_at');
        });
    }
};