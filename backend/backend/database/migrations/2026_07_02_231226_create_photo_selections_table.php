<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_selections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                  ->constrained('photo_projects')
                  ->cascadeOnDelete();

            $table->foreignId('photo_file_id')
                  ->constrained('photo_files')
                  ->cascadeOnDelete();

            $table->text('customer_note')->nullable();
            $table->timestamp('selected_at')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'photo_file_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_selections');
    }
};