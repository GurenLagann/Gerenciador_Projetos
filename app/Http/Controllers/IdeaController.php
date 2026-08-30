<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\IdeaStatus;
use App\Services\IdeaConversionService;
use App\Support\ResolvesIdeaStatusAndPriority;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdeaController extends Controller
{
    use ResolvesIdeaStatusAndPriority;

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

    public function convert(Idea $idea, IdeaConversionService $converter)
    {
        $project = $converter->convert($idea);

        return redirect()->route('projects.show', $project)->with('success', 'Idea converted to project!');
    }
}
