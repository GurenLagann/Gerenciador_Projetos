<?php

use Database\Seeders\IdeaPrioritySeeder;
use Database\Seeders\IdeaStatusSeeder;
use Database\Seeders\ProjectStatusSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('color');
            $table->string('bg');
            $table->string('bar');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('idea_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('color');
            $table->string('bg');
            $table->string('border')->nullable();
            $table->string('header_bg')->nullable();
            $table->boolean('is_board_column')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('idea_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('dot');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        (new ProjectStatusSeeder)->run();
        (new IdeaStatusSeeder)->run();
        (new IdeaPrioritySeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_priorities');
        Schema::dropIfExists('idea_statuses');
        Schema::dropIfExists('project_statuses');
    }
};
