<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $order = $project->milestones()->max('order') + 1;
        $project->milestones()->create(array_merge($validated, ['order' => $order]));

        return back()->with('success', 'Milestone added.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $milestone->update($validated);

        return back()->with('success', 'Milestone updated.');
    }

    public function toggle(Request $request, Project $project, Milestone $milestone)
    {
        $milestone->update([
            'completed' => ! $milestone->completed,
            'completed_at' => ! $milestone->completed ? now() : null,
        ]);

        return back()->with('success', 'Milestone toggled.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();

        return back()->with('success', 'Milestone deleted.');
    }

    public function reorder(Request $request, Project $project)
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $index => $id) {
            Milestone::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
