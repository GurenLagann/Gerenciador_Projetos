<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdeaStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'raw', 'label' => 'Bruto', 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)', 'border' => 'rgba(148,163,184,.2)', 'header_bg' => 'rgba(148,163,184,.07)', 'is_board_column' => true, 'sort_order' => 1],
            ['code' => 'exploring', 'label' => 'Explorando', 'color' => '#60a5fa', 'bg' => 'rgba(96,165,250,.13)', 'border' => 'rgba(96,165,250,.25)', 'header_bg' => 'rgba(96,165,250,.07)', 'is_board_column' => true, 'sort_order' => 2],
            ['code' => 'validated', 'label' => 'Validado', 'color' => '#6ee7b7', 'bg' => 'rgba(110,231,183,.12)', 'border' => 'rgba(110,231,183,.25)', 'header_bg' => 'rgba(110,231,183,.07)', 'is_board_column' => true, 'sort_order' => 3],
            ['code' => 'parked', 'label' => 'Pausado', 'color' => '#fcd34d', 'bg' => 'rgba(252,211,77,.12)', 'border' => 'rgba(252,211,77,.22)', 'header_bg' => 'rgba(252,211,77,.07)', 'is_board_column' => true, 'sort_order' => 4],
            ['code' => 'converted', 'label' => 'Convertido', 'color' => '#c4b5fd', 'bg' => 'rgba(196,181,253,.12)', 'border' => null, 'header_bg' => null, 'is_board_column' => false, 'sort_order' => 5],
        ];

        foreach ($statuses as $status) {
            DB::table('idea_statuses')->updateOrInsert(
                ['code' => $status['code']],
                $status + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
