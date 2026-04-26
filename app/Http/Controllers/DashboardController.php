<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_projects' => Project::count(),
            'in_progress' => Project::where('status', 'in-progress')->count(),
            'done' => Project::where('status', 'done')->count(),
            'ideas' => Idea::count(),
        ];

        $recentProjects = Project::latest()->take(6)->get();
        $recentIdeas = Idea::latest()->take(5)->get();
        $statusCounts = Project::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        return view('dashboard.index', compact('stats', 'recentProjects', 'recentIdeas', 'statusCounts'));
    }
}
