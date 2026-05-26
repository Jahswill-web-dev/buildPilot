<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Services\ActionTasks\ActionTasks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdeaTaskController extends Controller
{
    public function __construct(private readonly ActionTasks $actionTasks)
    {
    }

    public function index(Idea $idea): Response
    {
        $this->authorizeOwner($idea);

        return Inertia::render('Ideas/Tasks', [
            'idea' => [
                'id' => $idea->id,
                'name' => $idea->name,
                'description' => $idea->description,
                'actionTasks' => $this->actionTasks->normalizeStored($idea->action_tasks),
            ],
        ]);
    }

    public function phase(Idea $idea, string $category, string $phaseSlug): Response
    {
        $this->authorizeOwner($idea);

        $tasks = collect($this->actionTasks->normalizeStored($idea->action_tasks))
            ->filter(fn (array $task): bool => $task['category'] === $category && $task['phaseSlug'] === $phaseSlug)
            ->values()
            ->all();

        abort_if($tasks === [], 404);

        return Inertia::render('Ideas/TaskPhase', [
            'idea' => [
                'id' => $idea->id,
                'name' => $idea->name,
                'description' => $idea->description,
            ],
            'category' => $category,
            'phase' => [
                'name' => $tasks[0]['phase'],
                'slug' => $phaseSlug,
                'tasks' => $tasks,
            ],
        ]);
    }

    public function update(Request $request, Idea $idea, string $taskId): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed'],
        ]);

        $found = false;
        $tasks = collect($this->actionTasks->normalizeStored($idea->action_tasks))
            ->map(function (array $task) use ($taskId, $validated, &$found): array {
                if ($task['id'] !== $taskId) {
                    return $task;
                }

                $found = true;

                return [
                    ...$task,
                    'status' => $validated['status'],
                ];
            })
            ->values()
            ->all();

        abort_unless($found, 404);

        $idea->update(['action_tasks' => $tasks]);

        return redirect()->back()->with('success', 'Task updated.');
    }

    private function authorizeOwner(Idea $idea): void
    {
        abort_unless($idea->user_id === auth()->id(), 403);
    }
}
