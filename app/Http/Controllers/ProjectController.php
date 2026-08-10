<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\ProjectScannerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::withCount('milestones')->with('status');

        if ($request->status) {
            $query->whereRelation('status', 'code', $request->status);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'progress' => $query->orderByDesc('progress'),
            'status' => $query->join('project_statuses', 'project_statuses.id', '=', 'projects.status_id')
                ->orderBy('project_statuses.sort_order')
                ->select('projects.*'),
            default => $query->latest(),
        };

        $projects = $query->paginate(12)->withQueryString();
        $statuses = ProjectStatus::orderBy('sort_order')->get();

        return view('projects.index', compact('projects', 'statuses'));
    }

    public function show(Project $project)
    {
        $project->load(['milestones', 'annotations', 'tags', 'status']);
        $statuses = ProjectStatus::orderBy('sort_order')->get();

        return view('projects.show', compact('project', 'statuses'));
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
        $request->validate(['status' => ['required', Rule::exists('project_statuses', 'code')]]);
        $statusId = ProjectStatus::where('code', $request->status)->value('id');
        $project->update(['status_id' => $statusId]);

        return back()->with('success', 'Status updated.');
    }

    public function updateProgress(Request $request, Project $project)
    {
        $request->validate(['progress' => 'required|integer|min:0|max:100']);
        $project->update(['progress' => $request->progress]);

        return back()->with('success', 'Progress updated.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projeto removido.');
    }

    public function scan(ProjectScannerService $scanner)
    {
        $results = $scanner->scan();
        $count = count($results);

        return back()->with('success', "Scan complete. {$count} projects found.");
    }
}
