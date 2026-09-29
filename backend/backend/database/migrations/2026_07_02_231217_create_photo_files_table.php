<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('project_id')
                  ->constrained('photo_projects')
                  ->cascadeOnDelete();

            $table->string('type');           // preview / edited / final
            $table->string('filename');
            $table->string('path');           // path di MinIO
            $table->string('disk')->default('s3');
            $table->bigInteger('size')->default(0);
            $table->string('mime_type')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index('project_id');
            $table->index('type');
            $table->index('is_selected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_files');
    }
};