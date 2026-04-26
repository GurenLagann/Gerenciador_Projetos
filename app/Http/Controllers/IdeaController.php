<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IdeaController extends Controller
{
    public function index()
    {
        $ideas = Idea::with('tags')->get()->groupBy('status');
        $columns = ['raw', 'exploring', 'validated', 'parked'];
        return view('ideas.index', compact('ideas', 'columns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => 'nullable|in:raw,exploring,validated,parked',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        Idea::create($validated);
        return back()->with('success', 'Idea created.');
    }

    public function show(Idea $idea)
    {
        $idea->load(['annotations', 'tags']);
        return view('ideas.show', compact('idea'));
    }

    public function update(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => 'nullable|in:raw,exploring,validated,parked,converted',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $idea->update($validated);
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
            $slug = $originalSlug . '-' . $i++;
        }

        $project = Project::create([
            'name' => $idea->title,
            'slug' => $slug,
            'path' => 'ideas/' . $slug,
            'description' => $idea->description,
            'status' => 'planning',
        ]);

        $idea->update(['status' => 'converted', 'converted_to_project_id' => $project->id]);

        return redirect()->route('projects.show', $project)->with('success', 'Idea converted to project!');
    }
}
