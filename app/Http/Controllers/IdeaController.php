<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\IdeaPriority;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IdeaController extends Controller
{
    public function index()
    {
        $ideas = Idea::with(['tags', 'status', 'priority'])->get()->groupBy(fn (Idea $idea) => $idea->status->code);
        $columns = IdeaStatus::where('is_board_column', true)->orderBy('sort_order')->get();

        return view('ideas.index', compact('ideas', 'columns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => ['nullable', Rule::exists('idea_statuses', 'code')],
            'priority' => ['nullable', Rule::exists('idea_priorities', 'code')],
        ]);

        Idea::create($this->resolveStatusAndPriority($validated));

        return back()->with('success', 'Idea created.');
    }

    public function show(Idea $idea)
    {
        $idea->load(['annotations', 'tags', 'status', 'priority']);
        $statuses = IdeaStatus::orderBy('sort_order')->get();

        return view('ideas.show', compact('idea', 'statuses'));
    }

    public function update(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => ['nullable', Rule::exists('idea_statuses', 'code')],
            'priority' => ['nullable', Rule::exists('idea_priorities', 'code')],
        ]);

        $idea->update($this->resolveStatusAndPriority($validated));

        return back()->with('success', 'Idea updated.');
    }

    public function destroy(Idea $idea)
    {
        $idea->delete();

        return back()->with('success', 'Idea deleted.');
    }

    public function convert(Idea $idea)
    {
        $slug = Str::slug($idea->title);
        $originalSlug = $slug;
        $i = 1;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$i++;
        }

        $project = Project::create([
            'name' => $idea->title,
            'slug' => $slug,
            'path' => 'ideas/'.$slug,
            'description' => $idea->description,
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $idea->update([
            'status_id' => IdeaStatus::where('code', 'converted')->value('id'),
            'converted_to_project_id' => $project->id,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Idea converted to project!');
    }

    private function resolveStatusAndPriority(array $validated): array
    {
        if (array_key_exists('status', $validated)) {
            $validated['status_id'] = IdeaStatus::where('code', $validated['status'])->value('id');
            unset($validated['status']);
        }

        if (array_key_exists('priority', $validated)) {
            $validated['priority_id'] = IdeaPriority::where('code', $validated['priority'])->value('id');
            unset($validated['priority']);
        }

        return $validated;
    }
}
