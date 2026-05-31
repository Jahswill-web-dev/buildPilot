<?php

namespace App\Services\Ai;

use App\Services\ActionPhases\ActionPhases;
use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\DesiredOutcomes\DesiredOutcome;
use App\Services\MvpScopes\MvpScope;
use App\Services\ProblemStatements\ProblemStatement;
use App\Services\TargetUsers\TargetUserProfile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class RoadmapGenerator
{
    public function __construct(
        private readonly ActionPhases $actionPhases,
        private readonly ChecklistItems $checklistItems,
        private readonly CoreFeatures $coreFeatures,
        private readonly DesiredOutcome $desiredOutcome,
        private readonly MvpScope $mvpScope,
        private readonly ProblemStatement $problemStatement,
        private readonly TargetUserProfile $targetUserProfile,
    ) {
    }

    public function generate(string $ideaTitle, string $ideaDescription): array
    {
        if (! config('services.openai.api_key')) {
            Log::warning('AI roadmap generation skipped because OPENAI_API_KEY is not configured.');

            return $this->fallback();
        }

        try {
            $timeout = (int) config('services.openai.roadmap_timeout', 120);

            if (function_exists('set_time_limit')) {
                set_time_limit(max($timeout + 10, 30));
            }

            $scriptPath = (string) config(
                'services.openai.roadmap_script',
                base_path('resources/js/ai/generate-roadmap.mjs'),
            );

            $process = new Process([
                (string) config('services.openai.node_binary', 'node'),
                $scriptPath,
            ], base_path());

            $process->setEnv([
                'OPENAI_API_KEY' => config('services.openai.api_key'),
                'OPENAI_CHECKLIST_MODEL' => config('services.openai.checklist_model', 'gpt-5-nano'),
            ]);
            $process->setTimeout($timeout);
            $process->setInput(json_encode([
                'idea_title' => $ideaTitle,
                'idea_description' => $ideaDescription,
                'model' => config('services.openai.checklist_model', 'gpt-5-nano'),
            ], JSON_THROW_ON_ERROR));
            $process->mustRun();

            $roadmap = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $stageErrors = array_filter((array) ($roadmap['stage_errors'] ?? []));

            if ($stageErrors !== []) {
                Log::warning('AI roadmap generation completed with failed stages.', [
                    'failed_stages' => array_keys($stageErrors),
                    'stage_errors' => $stageErrors,
                ]);
            }

            $targetUser = $this->targetUserProfile->fromAiResponse($roadmap['target_user'] ?? null);
            $problemStatement = $this->problemStatement->fromAiResponse($roadmap['problem_statement'] ?? null);
            $desiredOutcome = $this->desiredOutcome->fromAiResponse($roadmap['desired_outcome'] ?? null);
            $coreFeatures = $this->coreFeatures->fromAiResponse($roadmap['core_features'] ?? null);
            $mvpScope = $this->mvpScope->fromAiResponse($roadmap['mvp_scope'] ?? null);
            $phases = $this->actionPhases->fromAiResponse($roadmap['phases'] ?? null);
            $checklist = $this->checklistItems->fromAiResponse($roadmap['checklist'] ?? null);

            return [
                'target_user' => $targetUser === [] ? $this->targetUserProfile->failed() : $targetUser,
                'problem_statement' => $problemStatement === '' ? $this->problemStatement->failed() : $problemStatement,
                'desired_outcome' => $desiredOutcome === '' ? $this->desiredOutcome->failed() : $desiredOutcome,
                'core_features' => $coreFeatures === [] ? $this->coreFeatures->failed() : $coreFeatures,
                'mvp_scope' => $mvpScope === [] ? $this->mvpScope->failed() : $mvpScope,
                'action_phases' => $phases === [] ? $this->actionPhases->fallback() : $phases,
                'checklist' => $checklist === [] ? $this->checklistItems->failed() : $checklist,
            ];
        } catch (Throwable $error) {
            Log::warning('AI roadmap generation failed.', [
                'message' => $error->getMessage(),
                'stderr' => isset($process) ? trim($process->getErrorOutput()) : null,
            ]);

            return $this->fallback();
        } finally {
            if (function_exists('set_time_limit')) {
                set_time_limit(0);
            }
        }
    }

    private function fallback(): array
    {
        return [
            'target_user' => $this->targetUserProfile->fallback(),
            'problem_statement' => $this->problemStatement->fallback(),
            'desired_outcome' => $this->desiredOutcome->fallback(),
            'core_features' => $this->coreFeatures->fallback(),
            'mvp_scope' => $this->mvpScope->fallback(),
            'action_phases' => $this->actionPhases->fallback(),
            'checklist' => $this->checklistItems->fallback(),
        ];
    }
}
