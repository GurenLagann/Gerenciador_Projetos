<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('runtime_version')->nullable()->after('tech_stack');
            $table->string('framework_version')->nullable()->after('runtime_version');
            $table->string('database_engine')->nullable()->after('framework_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['runtime_version', 'framework_version', 'database_engine']);
        });
    }
};
