<?php

namespace App\Services\Ai;

use App\Models\Idea;
use App\Services\ActionTasks\ActionTasks;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PhaseTaskGenerator
{
    public function __construct(private readonly ActionTasks $actionTasks)
    {
    }

    public function generate(Idea $idea, array $phase, array $completedTasks): array
    {
        if (! config('services.openai.api_key')) {
            throw new RuntimeException('OpenAI API key is missing.');
        }

        try {
            $timeout = (int) config('services.openai.phase_tasks_timeout', 120);

            if (function_exists('set_time_limit')) {
                set_time_limit(max($timeout + 10, 30));
            }

            $process = new Process([
                (string) config('services.openai.node_binary', 'node'),
                (string) config('services.openai.phase_tasks_script', base_path('resources/js/ai/generate-phase-tasks.mjs')),
            ], base_path());

            $process->setEnv([
                'OPENAI_API_KEY' => config('services.openai.api_key'),
                'OPENAI_CHECKLIST_MODEL' => config('services.openai.checklist_model', 'gpt-5-nano'),
            ]);
            $process->setTimeout($timeout);
            $process->setInput(json_encode([
                'model' => config('services.openai.checklist_model', 'gpt-5-nano'),
                'product_description' => $idea->description,
                'target_users' => $idea->target_user,
                'problem_statement' => $idea->problem_statement,
                'desired_outcome' => $idea->desired_outcome,
                'core_features' => $idea->core_features,
                'mvp_scope' => $idea->mvp_scope,
                'phase_title' => $phase['title'] ?? $phase['name'] ?? '',
                'phase_description' => $phase['description'] ?? '',
                'phase_goal' => $phase['goal'] ?? '',
                'phase_primary_category' => $phase['primaryCategory'] ?? 'product',
                'phase_included_categories' => $phase['includedCategories'] ?? ['product'],
                'completed_tasks' => $completedTasks,
            ], JSON_THROW_ON_ERROR));
            $process->mustRun();

            $payload = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $tasks = $this->actionTasks->fromAiPhaseResponse($payload, $phase, $phase['slug'] ?? 'phase');

            if ($tasks === []) {
                throw new RuntimeException('AI returned no usable phase tasks.');
            }

            return $tasks;
        } catch (Throwable $error) {
            throw new RuntimeException('Phase task generation failed: '.$error->getMessage(), previous: $error);
        } finally {
            if (function_exists('set_time_limit')) {
                set_time_limit(0);
            }
        }
    }
}
