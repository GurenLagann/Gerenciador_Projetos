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
            // No ->after() modifier: this app's database is SQLite, whose
            // grammar doesn't support column positioning — it's silently a
            // no-op there, so the column lands at the end of the table
            // regardless of where this line appears.
            $table->unsignedBigInteger('size_bytes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('size_bytes');
        });
    }
};
