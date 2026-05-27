<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\IdeaTaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [IdeaController::class, 'index'])->name('home');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
    Route::patch('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');

    Route::get('/ideas/{idea}/tasks', [IdeaTaskController::class, 'index'])
        ->name('ideas.tasks.index');
    Route::get('/ideas/{idea}/tasks/phases/{phaseSlug}', [IdeaTaskController::class, 'phaseOverview'])
        ->name('ideas.tasks.phases.show');
    Route::post('/ideas/{idea}/tasks/phases/{phaseSlug}/generate', [IdeaTaskController::class, 'generatePhaseTasks'])
        ->name('ideas.tasks.phases.generate');
    Route::get('/ideas/{idea}/tasks/items/{taskId}', [IdeaTaskController::class, 'show'])
        ->name('ideas.tasks.show');
    Route::get('/ideas/{idea}/tasks/{category}/{phaseSlug}', [IdeaTaskController::class, 'phase'])
        ->name('ideas.tasks.phase');
    Route::patch('/ideas/{idea}/tasks/{taskId}', [IdeaTaskController::class, 'update'])
        ->name('ideas.tasks.update');

    Route::post('/ideas/{idea}/checklist-items', [ChecklistItemController::class, 'store'])
        ->name('ideas.checklist-items.store');
    Route::patch('/ideas/{idea}/checklist-items/{itemId}', [ChecklistItemController::class, 'update'])
        ->name('ideas.checklist-items.update');
    Route::delete('/ideas/{idea}/checklist-items/{itemId}', [ChecklistItemController::class, 'destroy'])
        ->name('ideas.checklist-items.destroy');
});

Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/contact', fn () => Inertia::render('Contact'))->name('contact');
