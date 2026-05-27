<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Services\ActionPhases\ActionPhases;
use App\Services\ActionTasks\ActionTasks;
use App\Services\Ai\PhaseTaskGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class IdeaTaskController extends Controller
{
    public function __construct(
        private readonly ActionPhases $actionPhases,
        private readonly ActionTasks $actionTasks,
        private readonly PhaseTaskGenerator $phaseTaskGenerator,
    ) {
    }

    public function index(Idea $idea): Response
    {
        $this->authorizeOwner($idea);

        return Inertia::render('Ideas/Tasks', [
            'idea' => [
                'id' => $idea->id,
                'name' => $idea->name,
                'description' => $idea->description,
                'actionPhases' => $this->actionPhases->normalizeStored($idea->action_phases),
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
                'title' => $tasks[0]['phase'],
                'slug' => $phaseSlug,
                'description' => '',
                'goal' => '',
                'successCriteria' => '',
                'tasks' => $tasks,
            ],
        ]);
    }

    public function phaseOverview(Idea $idea, string $phaseSlug): Response
    {
        $this->authorizeOwner($idea);

        $phase = collect($this->actionPhases->normalizeStored($idea->action_phases))
            ->firstWhere('slug', $phaseSlug);

        abort_unless($phase, 404);

        $tasks = collect($this->actionTasks->normalizeStored($idea->action_tasks))
            ->filter(fn (array $task): bool => $task['phaseSlug'] === $phaseSlug)
            ->values()
            ->all();

        return Inertia::render('Ideas/TaskPhase', [
            'idea' => [
                'id' => $idea->id,
                'name' => $idea->name,
                'description' => $idea->description,
            ],
            'category' => null,
            'phase' => [
                ...$phase,
                'name' => $phase['title'],
                'slug' => $phaseSlug,
                'tasks' => $tasks,
            ],
        ]);
    }

    public function show(Idea $idea, string $taskId): Response
    {
        $this->authorizeOwner($idea);

        $task = collect($this->actionTasks->normalizeStored($idea->action_tasks))
            ->firstWhere('id', $taskId);

        abort_unless($task, 404);

        $phase = collect($this->actionPhases->normalizeStored($idea->action_phases))
            ->firstWhere('slug', $task['phaseSlug']);

        return Inertia::render('Ideas/TaskShow', [
            'idea' => [
                'id' => $idea->id,
                'name' => $idea->name,
                'description' => $idea->description,
            ],
            'task' => $task,
            'phase' => $phase ? [
                ...$phase,
                'name' => $phase['title'],
            ] : [
                'name' => $task['phase'],
                'title' => $task['phase'],
                'slug' => $task['phaseSlug'],
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

    public function generatePhaseTasks(Idea $idea, string $phaseSlug): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $phase = collect($this->actionPhases->normalizeStored($idea->action_phases))
            ->firstWhere('slug', $phaseSlug);

        abort_unless($phase, 404);

        $currentTasks = $this->actionTasks->normalizeStored($idea->action_tasks);
        $completedTasks = collect($currentTasks)
            ->filter(fn (array $task): bool => $task['status'] === ActionTasks::COMPLETED)
            ->values()
            ->all();

        try {
            $generatedTasks = $this->phaseTaskGenerator->generate($idea, $phase, $completedTasks);
        } catch (Throwable) {
            return redirect()
                ->back()
                ->withErrors(['tasks' => 'Task generation failed. Try again in a moment.']);
        }

        $tasks = collect($currentTasks)
            ->reject(fn (array $task): bool => $task['phaseSlug'] === $phaseSlug)
            ->merge($generatedTasks)
            ->values()
            ->all();

        $idea->update(['action_tasks' => $tasks]);

        return redirect()->back()->with('success', 'Phase tasks generated.');
    }

    private function authorizeOwner(Idea $idea): void
    {
        abort_unless($idea->user_id === auth()->id(), 403);
    }
}
