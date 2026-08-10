<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdeaPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['code' => 'low', 'label' => 'Baixa', 'dot' => '#4e6080', 'sort_order' => 1],
            ['code' => 'medium', 'label' => 'Media', 'dot' => '#f59e0b', 'sort_order' => 2],
            ['code' => 'high', 'label' => 'Alta', 'dot' => '#ef4444', 'sort_order' => 3],
        ];

        foreach ($priorities as $priority) {
            DB::table('idea_priorities')->updateOrInsert(
                ['code' => $priority['code']],
                $priority + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
