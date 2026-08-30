<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\TechnicalDebt;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_projects' => Project::count(),
            'in_progress' => Project::whereRelation('status', 'code', 'in-progress')->count(),
            'done' => Project::whereRelation('status', 'code', 'done')->count(),
            'ideas' => Idea::count(),
        ];

        $recentProjects = Project::with('status')->latest()->take(6)->get();
        $recentIdeas = Idea::with(['status', 'priority'])->latest()->take(5)->get();
        $activeProjects = Project::with('status')->whereRelation('status', 'code', 'in-progress')->latest()->take(4)->get();
        $statusCounts = Project::with('status')
            ->selectRaw('status_id, count(*) as count')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->code => (object) ['count' => $row->count, 'status' => $row->status]]);

        $openDebtCount = TechnicalDebt::where('resolved', false)->count();

        $topDebtProjects = Project::withCount(['technicalDebts as open_debt_count' => function ($q) {
            $q->where('resolved', false);
        }])
            ->get()
            ->filter(fn ($p) => $p->open_debt_count > 0)
            ->sortByDesc('open_debt_count')
            ->take(5)
            ->values();

        $ideaStatusCounts = Idea::with('status')
            ->selectRaw('status_id, count(*) as count')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->code => (object) ['count' => $row->count, 'status' => $row->status]]);

        $ideaPriorityCounts = Idea::with('priority')
            ->selectRaw('priority_id, count(*) as count')
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->priority->code => (object) ['count' => $row->count, 'priority' => $row->priority]]);

        $upcomingMilestones = Milestone::with('project')
            ->whereNotNull('due_date')
            ->where('completed', false)
            ->orderBy('due_date')
            ->take(6)
            ->get();

        // Lightweight payload for the ⌘K quick-switcher (small dataset — fine to load in full).
        $paletteProjects = Project::orderBy('name')->get(['name', 'slug'])->map(fn ($p) => [
            'label' => $p->name,
            'url' => route('projects.show', $p),
        ]);
        $paletteIdeas = Idea::orderBy('title')->get(['id', 'title'])->map(fn ($i) => [
            'label' => $i->title,
            'url' => route('ideas.show', $i),
        ]);

        return view('dashboard.index', compact(
            'stats', 'recentProjects', 'recentIdeas', 'activeProjects', 'statusCounts',
            'openDebtCount', 'topDebtProjects', 'ideaStatusCounts', 'ideaPriorityCounts', 'upcomingMilestones',
            'paletteProjects', 'paletteIdeas'
        ));
    }
}
