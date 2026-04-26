<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Project;
use Illuminate\Http\Request;

class AnnotationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annotatable_type' => 'required|in:project,idea',
            'annotatable_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'color' => 'nullable|string|max:20',
        ]);

        $model = $validated['annotatable_type'] === 'project'
            ? Project::findOrFail($validated['annotatable_id'])
            : Idea::findOrFail($validated['annotatable_id']);

        $annotation = $model->annotations()->create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'color' => $validated['color'] ?? null,
        ]);

        return back()->with('success', 'Annotation added.');
    }

    public function update(Request $request, Annotation $annotation)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'color' => 'nullable|string|max:20',
        ]);

        $annotation->update($validated);
        return back()->with('success', 'Annotation updated.');
    }

    public function destroy(Annotation $annotation)
    {
        $annotation->delete();
        return back()->with('success', 'Annotation deleted.');
    }

    public function pin(Annotation $annotation)
    {
        $annotation->update(['pinned' => !$annotation->pinned]);
        return back()->with('success', 'Annotation pin toggled.');
    }
}
