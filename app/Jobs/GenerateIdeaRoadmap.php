<?php

namespace App\Jobs;

use App\Models\Idea;
use App\Services\ActionPhases\ActionPhases;
use App\Services\ActionTasks\ActionTasks;
use App\Services\Ai\RoadmapGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateIdeaRoadmap implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(private readonly int $ideaId)
    {
    }

    public function handle(
        RoadmapGenerator $roadmapGenerator,
        ActionPhases $actionPhases,
        ActionTasks $actionTasks,
    ): void {
        $idea = Idea::find($this->ideaId);

        if (! $idea) {
            return;
        }

        try {
            $roadmap = $roadmapGenerator->generate($idea->name, $idea->description);

            $idea->update([
                'target_user' => $roadmap['target_user'],
                'problem_statement' => $roadmap['problem_statement'],
                'desired_outcome' => $roadmap['desired_outcome'],
                'core_features' => $roadmap['core_features'],
                'mvp_scope' => $roadmap['mvp_scope'],
                'action_phases' => $roadmap['action_phases'] ?? $actionPhases->fallback(),
                'action_tasks' => $actionTasks->fallback(),
                'checklist' => $roadmap['checklist'],
                'state' => 'done',
            ]);
        } catch (Throwable $error) {
            Log::warning('Queued roadmap generation failed.', [
                'idea_id' => $this->ideaId,
                'message' => $error->getMessage(),
            ]);

            $idea->update(['state' => 'failed']);
        }
    }

    public function failed(Throwable $error): void
    {
        Idea::whereKey($this->ideaId)->update(['state' => 'failed']);

        Log::warning('Queued roadmap generation job failed.', [
            'idea_id' => $this->ideaId,
            'message' => $error->getMessage(),
        ]);
    }
}
