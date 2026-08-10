<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'idea', 'label' => 'Ideia', 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)', 'bar' => '#64748b', 'sort_order' => 1],
            ['code' => 'planning', 'label' => 'Planejamento', 'color' => '#60a5fa', 'bg' => 'rgba(96,165,250,.13)', 'bar' => '#3b82f6', 'sort_order' => 2],
            ['code' => 'in-progress', 'label' => 'Em Andamento', 'color' => '#a5b4fc', 'bg' => 'rgba(165,180,252,.13)', 'bar' => '#6366f1', 'sort_order' => 3],
            ['code' => 'paused', 'label' => 'Pausado', 'color' => '#fcd34d', 'bg' => 'rgba(252,211,77,.12)', 'bar' => '#f59e0b', 'sort_order' => 4],
            ['code' => 'done', 'label' => 'Concluído', 'color' => '#6ee7b7', 'bg' => 'rgba(110,231,183,.12)', 'bar' => '#10b981', 'sort_order' => 5],
            ['code' => 'archived', 'label' => 'Arquivado', 'color' => '#fca5a5', 'bg' => 'rgba(252,165,165,.12)', 'bar' => '#ef4444', 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            DB::table('project_statuses')->updateOrInsert(
                ['code' => $status['code']],
                $status + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
