<?php

namespace App\Services\ActionPhases;

use Illuminate\Support\Str;

class ActionPhases
{
    private const VALID_CATEGORIES = [
        'validation',
        'product',
        'marketing',
    ];

    private const FALLBACK_PHASES = [
        [
            'title' => 'Validate the Problem',
            'description' => 'Confirm the audience, pain, current workaround, and reason someone would care before committing to a build.',
            'primaryCategory' => 'validation',
            'includedCategories' => ['validation', 'product'],
            'goal' => 'Know whether the problem is painful enough for a focused MVP.',
            'successCriteria' => 'A narrow target user, clear problem statement, and strongest assumptions are documented.',
            'order' => 1,
        ],
        [
            'title' => 'Design the MVP Path',
            'description' => 'Turn the validation insight into the smallest product flow and launch promise that can prove the idea.',
            'primaryCategory' => 'product',
            'includedCategories' => ['product', 'validation'],
            'goal' => 'Define what the first usable version must do and what it should deliberately skip.',
            'successCriteria' => 'The core flow, must-have scope, and feedback questions are clear enough to build.',
            'order' => 2,
        ],
        [
            'title' => 'Build the First Usable Version',
            'description' => 'Create the minimum product experience needed for early users to reach the promised outcome.',
            'primaryCategory' => 'product',
            'includedCategories' => ['product'],
            'goal' => 'Ship a working MVP that covers the must-have flow.',
            'successCriteria' => 'The MVP can be used end to end by a real target user without manual explanation.',
            'order' => 3,
        ],
        [
            'title' => 'Prepare the MVP Launch',
            'description' => 'Package the offer, choose the first distribution channel, and prepare a simple launch message.',
            'primaryCategory' => 'marketing',
            'includedCategories' => ['marketing', 'validation'],
            'goal' => 'Put the MVP in front of the first reachable audience.',
            'successCriteria' => 'Launch copy, audience, channel, and response path are ready.',
            'order' => 4,
        ],
        [
            'title' => 'Improve From Early Feedback',
            'description' => 'Use real user reactions to decide what to fix, keep, remove, or test next.',
            'primaryCategory' => 'validation',
            'includedCategories' => ['validation', 'product', 'marketing'],
            'goal' => 'Learn whether the MVP is solving the right problem for the right users.',
            'successCriteria' => 'Early feedback is reviewed and the next product or growth move is prioritized.',
            'order' => 5,
        ],
    ];

    public function fallback(): array
    {
        return collect(self::FALLBACK_PHASES)
            ->map(fn (array $phase): array => $this->normalizeStoredPhase($phase))
            ->values()
            ->all();
    }

    public function fromAiResponse(mixed $phases): array
    {
        $items = is_array($phases) && array_key_exists('phases', $phases) ? $phases['phases'] : $phases;

        return $this->normalize($items, false);
    }

    public function normalizeStored(mixed $phases): array
    {
        $normalized = $this->normalize($phases, true);

        return $normalized === [] ? $this->fallback() : $normalized;
    }

    private function normalize(mixed $phases, bool $allowFallbackFields): array
    {
        return collect(is_array($phases) ? $phases : [])
            ->map(fn (mixed $phase): array => $this->normalizeStoredPhase($phase, $allowFallbackFields))
            ->filter(fn (array $phase): bool => $phase['title'] !== '')
            ->sortBy('order')
            ->values()
            ->all();
    }

    private function normalizeStoredPhase(mixed $phase, bool $allowFallbackFields = true): array
    {
        if (! is_array($phase)) {
            return $this->blankPhase();
        }

        $title = $this->limit($phase['title'] ?? $phase['name'] ?? '', 90);
        $includedCategories = $this->normalizeIncludedCategories(
            $phase['includedCategories'] ?? $phase['included_categories'] ?? [],
        );
        $primaryCategory = $this->normalizeCategory(
            $phase['primaryCategory'] ?? $phase['primary_category'] ?? ($includedCategories[0] ?? 'product'),
        );

        if ($includedCategories === []) {
            $includedCategories = [$primaryCategory];
        }

        return [
            'title' => $title,
            'name' => $title,
            'slug' => Str::slug($title === '' ? 'phase' : $title),
            'description' => $this->limit($phase['description'] ?? '', 500),
            'primaryCategory' => $primaryCategory,
            'includedCategories' => $includedCategories,
            'goal' => $this->limit($phase['goal'] ?? '', 500),
            'successCriteria' => $this->limit($phase['successCriteria'] ?? $phase['success_criteria'] ?? '', 500),
            'order' => max(1, (int) ($phase['order'] ?? ($allowFallbackFields ? 999 : 0))),
        ];
    }

    private function blankPhase(): array
    {
        return [
            'title' => '',
            'name' => '',
            'slug' => 'phase',
            'description' => '',
            'primaryCategory' => 'product',
            'includedCategories' => ['product'],
            'goal' => '',
            'successCriteria' => '',
            'order' => 999,
        ];
    }

    private function normalizeIncludedCategories(mixed $categories): array
    {
        return collect(is_array($categories) ? $categories : [])
            ->map(fn (mixed $category): string => $this->normalizeCategory($category))
            ->unique()
            ->values()
            ->all();
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
