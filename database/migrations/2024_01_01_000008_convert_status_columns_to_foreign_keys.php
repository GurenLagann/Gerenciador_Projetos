<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- projects.status -> projects.status_id ---
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')
                ->constrained('project_statuses')->restrictOnDelete();
        });

        $projectStatusMap = DB::table('project_statuses')->pluck('id', 'code');
        DB::table('projects')->select('id', 'status')->orderBy('id')->chunkById(200, function ($rows) use ($projectStatusMap) {
            foreach ($rows as $row) {
                DB::table('projects')->where('id', $row->id)->update([
                    'status_id' => $projectStatusMap[$row->status] ?? $projectStatusMap['idea'],
                ]);
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
        });

        // --- ideas.status -> ideas.status_id, ideas.priority -> ideas.priority_id ---
        Schema::table('ideas', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')
                ->constrained('idea_statuses')->restrictOnDelete();
            $table->foreignId('priority_id')->nullable()->after('priority')
                ->constrained('idea_priorities')->restrictOnDelete();
        });

        $ideaStatusMap = DB::table('idea_statuses')->pluck('id', 'code');
        $ideaPriorityMap = DB::table('idea_priorities')->pluck('id', 'code');
        DB::table('ideas')->select('id', 'status', 'priority')->orderBy('id')->chunkById(200, function ($rows) use ($ideaStatusMap, $ideaPriorityMap) {
            foreach ($rows as $row) {
                DB::table('ideas')->where('id', $row->id)->update([
                    'status_id' => $ideaStatusMap[$row->status] ?? $ideaStatusMap['raw'],
                    'priority_id' => $ideaPriorityMap[$row->priority] ?? $ideaPriorityMap['medium'],
                ]);
            }
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropColumn(['status', 'priority']);
        });
        Schema::table('ideas', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
            $table->foreignId('priority_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->nullable()->after('status_id');
        });
        DB::table('projects')->update([
            'status' => DB::raw('(select code from project_statuses where project_statuses.id = projects.status_id)'),
        ]);
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->string('status')->nullable()->after('status_id');
            $table->string('priority')->nullable()->after('priority_id');
        });
        DB::table('ideas')->update([
            'status' => DB::raw('(select code from idea_statuses where idea_statuses.id = ideas.status_id)'),
            'priority' => DB::raw('(select code from idea_priorities where idea_priorities.id = ideas.priority_id)'),
        ]);
        Schema::table('ideas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
            $table->dropConstrainedForeignId('priority_id');
        });
    }
};
