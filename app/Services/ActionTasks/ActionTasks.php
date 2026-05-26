<?php

namespace App\Services\ActionTasks;

use Illuminate\Support\Str;

class ActionTasks
{
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';

    private const VALID_STATUSES = [
        self::PENDING,
        self::COMPLETED,
    ];

    private const VALID_PRIORITIES = [
        'High',
        'Medium',
        'Low',
    ];

    private const VALID_CATEGORIES = [
        'product',
        'marketing',
        'validation',
    ];

    private const FALLBACK_TASKS = [
        [
            'id' => 'validate-problem-fit',
            'title' => 'Validate the problem with five target users',
            'description' => 'Ask focused questions to confirm the pain, current workaround, and urgency before building.',
            'phase' => 'Validate',
            'priority' => 'High',
            'category' => 'validation',
        ],
        [
            'id' => 'choose-target-user',
            'title' => 'Choose the first target user',
            'description' => 'Pick the narrow user group that feels the pain most often and can give useful feedback quickly.',
            'phase' => 'Validate',
            'priority' => 'High',
            'category' => 'validation',
        ],
        [
            'id' => 'define-pricing-hypothesis',
            'title' => 'Define a pricing hypothesis',
            'description' => 'Choose an initial price range and write down why this audience would pay for the outcome.',
            'phase' => 'Validate',
            'priority' => 'Medium',
            'category' => 'validation',
        ],
        [
            'id' => 'interview-potential-users',
            'title' => 'Interview potential users',
            'description' => 'Run short conversations to learn how they solve the problem today and what would make them switch.',
            'phase' => 'Validate',
            'priority' => 'High',
            'category' => 'validation',
        ],
        [
            'id' => 'check-competitors',
            'title' => 'Check direct and indirect competitors',
            'description' => 'List existing alternatives, compare their positioning, and identify one clear gap to test.',
            'phase' => 'Validate',
            'priority' => 'Medium',
            'category' => 'validation',
        ],
        [
            'id' => 'decide-mvp-scope',
            'title' => 'Decide the MVP scope',
            'description' => 'Lock the smallest version that can prove the problem, audience, and willingness to pay.',
            'phase' => 'Validate',
            'priority' => 'High',
            'category' => 'validation',
        ],
        [
            'id' => 'sketch-core-flow',
            'title' => 'Sketch the core user flow',
            'description' => 'Map the few screens or steps a user needs to reach the main outcome.',
            'phase' => 'Design',
            'priority' => 'Medium',
            'category' => 'product',
        ],
        [
            'id' => 'write-build-list',
            'title' => 'Turn must-haves into a build list',
            'description' => 'Break each must-have into small implementation tasks that can be finished independently.',
            'phase' => 'Build',
            'priority' => 'High',
            'category' => 'product',
        ],
        [
            'id' => 'ship-first-version',
            'title' => 'Ship the smallest usable version',
            'description' => 'Release only the must-have flow and avoid adding nice-to-have features before feedback.',
            'phase' => 'Build',
            'priority' => 'High',
            'category' => 'product',
        ],
        [
            'id' => 'define-launch-audience',
            'title' => 'Define the first launch audience',
            'description' => 'Pick the smallest reachable audience that feels the problem most clearly.',
            'phase' => 'Launch',
            'priority' => 'High',
            'category' => 'marketing',
        ],
        [
            'id' => 'prepare-launch-message',
            'title' => 'Prepare a simple launch message',
            'description' => 'Write a concise message that names the target user, pain, promise, and next action.',
            'phase' => 'Launch',
            'priority' => 'Medium',
            'category' => 'marketing',
        ],
        [
            'id' => 'choose-feedback-channel',
            'title' => 'Choose one feedback channel',
            'description' => 'Select the channel where early users can respond quickly after seeing the offer.',
            'phase' => 'Launch',
            'priority' => 'Low',
            'category' => 'marketing',
        ],
    ];

    public function fallback(): array
    {
        return collect(self::FALLBACK_TASKS)
            ->map(fn (array $task): array => [
                ...$task,
                'phaseSlug' => Str::slug($task['phase'] ?? 'Build'),
                'status' => self::PENDING,
            ])
            ->all();
    }

    public function normalizeStored(mixed $tasks): array
    {
        $normalized = collect(is_array($tasks) ? $tasks : [])
            ->map(fn (mixed $task): array => $this->normalizeStoredTask($task))
            ->filter(fn (array $task): bool => $task['title'] !== '')
            ->values()
            ->all();

        return $normalized === [] ? $this->fallback() : $normalized;
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES, true);
    }

    private function normalizeStoredTask(mixed $task): array
    {
        if (! is_array($task)) {
            return $this->blankTask();
        }

        return [
            'id' => $this->limit($task['id'] ?? Str::uuid(), 120),
            'title' => $this->limit($task['title'] ?? '', 140),
            'description' => $this->limit($task['description'] ?? '', 500),
            'status' => $this->normalizeStatus($task['status'] ?? self::PENDING),
            'phase' => $this->limit($task['phase'] ?? 'Build', 40),
            'phaseSlug' => Str::slug($this->limit($task['phase'] ?? 'Build', 40)),
            'priority' => $this->normalizePriority($task['priority'] ?? 'Medium'),
            'category' => $this->normalizeCategory($task['category'] ?? 'product'),
        ];
    }

    private function blankTask(): array
    {
        return [
            'id' => '',
            'title' => '',
            'description' => '',
            'status' => self::PENDING,
            'phase' => 'Build',
            'phaseSlug' => 'build',
            'priority' => 'Medium',
            'category' => 'product',
        ];
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = trim((string) $status);

        return $this->isValidStatus($status) ? $status : self::PENDING;
    }

    private function normalizePriority(mixed $priority): string
    {
        $priority = Str::of((string) $priority)->trim()->title()->toString();

        return in_array($priority, self::VALID_PRIORITIES, true) ? $priority : 'Medium';
    }

    private function normalizeCategory(mixed $category): string
    {
        $category = Str::of((string) $category)->trim()->lower()->toString();

        return in_array($category, self::VALID_CATEGORIES, true) ? $category : 'product';
    }

    private function limit(mixed $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }
}
