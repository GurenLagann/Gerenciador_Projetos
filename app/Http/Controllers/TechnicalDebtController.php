<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TechnicalDebt;
use Illuminate\Http\Request;

class TechnicalDebtController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $order = $project->technicalDebts()->max('order') + 1;
        $project->technicalDebts()->create(array_merge($validated, ['order' => $order]));

        return back()->with('success', 'Technical debt added.');
    }

    public function toggle(Project $project, TechnicalDebt $technicalDebt)
    {
        $technicalDebt->update([
            'resolved' => ! $technicalDebt->resolved,
            'resolved_at' => ! $technicalDebt->resolved ? now() : null,
        ]);

        return back()->with('success', 'Technical debt toggled.');
    }

    public function destroy(Project $project, TechnicalDebt $technicalDebt)
    {
        $technicalDebt->delete();

        return back()->with('success', 'Technical debt deleted.');
    }
}
