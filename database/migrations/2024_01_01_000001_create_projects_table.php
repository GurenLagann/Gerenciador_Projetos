<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('path')->unique();
            $table->text('description')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('detected_files')->nullable();
            $table->enum('status', ['idea', 'planning', 'in-progress', 'paused', 'done', 'archived'])->default('planning');
            $table->tinyInteger('progress')->default(0);
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_scanned')->default(false);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
