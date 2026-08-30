<?php

use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\TechnicalDebtController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Projects
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects/scan', [ProjectController::class, 'scan'])->name('projects.scan');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::patch('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
Route::patch('/projects/{project}/progress', [ProjectController::class, 'updateProgress'])->name('projects.progress');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// Milestones
Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
Route::patch('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
Route::patch('/projects/{project}/milestones/{milestone}/toggle', [MilestoneController::class, 'toggle'])->name('milestones.toggle');
Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');
Route::post('/projects/{project}/milestones/reorder', [MilestoneController::class, 'reorder'])->name('milestones.reorder');

// Technical debt
Route::post('/projects/{project}/technical-debts', [TechnicalDebtController::class, 'store'])->name('technical-debts.store');
Route::patch('/projects/{project}/technical-debts/{technicalDebt}/toggle', [TechnicalDebtController::class, 'toggle'])->name('technical-debts.toggle');
Route::delete('/projects/{project}/technical-debts/{technicalDebt}', [TechnicalDebtController::class, 'destroy'])->name('technical-debts.destroy');

// Annotations
Route::post('/annotations', [AnnotationController::class, 'store'])->name('annotations.store');
Route::patch('/annotations/{annotation}', [AnnotationController::class, 'update'])->name('annotations.update');
Route::delete('/annotations/{annotation}', [AnnotationController::class, 'destroy'])->name('annotations.destroy');
Route::patch('/annotations/{annotation}/pin', [AnnotationController::class, 'pin'])->name('annotations.pin');

// Ideas
Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
Route::patch('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
Route::post('/ideas/{idea}/convert', [IdeaController::class, 'convert'])->name('ideas.convert');
