<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::withCount('milestones');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'progress' => $query->orderByDesc('progress'),
            'status' => $query->orderBy('status'),
            default => $query->latest(),
        };

        $projects = $query->paginate(12)->withQueryString();
        $statuses = ['idea', 'planning', 'in-progress', 'paused', 'done', 'archived'];

        return view('projects.index', compact('projects', 'statuses'));
    }

    public function show(Project $project)
    {
        $project->load(['milestones', 'annotations', 'tags']);
        return view('projects.show', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }

    public function updateStatus(Request $request, Project $project)
    {
        $request->validate(['status' => 'required|in:idea,planning,in-progress,paused,done,archived']);
        $project->update(['status' => $request->status]);
        return back()->with('success', 'Status updated.');
    }

    public function updateProgress(Request $request, Project $project)
    {
        $request->validate(['progress' => 'required|integer|min:0|max:100']);
        $project->update(['progress' => $request->progress]);
        return back()->with('success', 'Progress updated.');
    }

    public function scan(ProjectScannerService $scanner)
    {
        $results = $scanner->scan();
        $count = count($results);
        return back()->with('success', "Scan complete. {$count} projects found.");
    }
}
