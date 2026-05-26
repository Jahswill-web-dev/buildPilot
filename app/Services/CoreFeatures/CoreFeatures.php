<?php

namespace App\Services\CoreFeatures;

use Illuminate\Support\Str;

class CoreFeatures
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const FALLBACK_FEATURES = [
        [
            'feature' => 'Idea capture',
            'reason' => 'Gives the founder one place to record the concept before refining it.',
        ],
        [
            'feature' => 'Target user definition',
            'reason' => 'Keeps the product focused on the first audience most likely to care.',
        ],
        [
            'feature' => 'Execution checklist',
            'reason' => 'Turns the idea into practical next steps instead of a vague plan.',
        ],
        [
            'feature' => 'Progress tracking',
            'reason' => 'Helps the founder see what has been completed and what still needs attention.',
        ],
    ];

    public function fallback(): array
    {
        return self::FALLBACK_FEATURES;
    }

    public function failed(): array
    {
        return [
            [
                'feature' => self::FAILURE_MESSAGE,
                'reason' => '',
            ],
        ];
    }

    public function fromAiResponse(mixed $features): array
    {
        if (! is_array($features)) {
            return [];
        }

        $normalized = collect($features)
            ->map(fn (mixed $feature): array => $this->normalizeItem($feature))
            ->filter(fn (array $feature): bool => $feature['feature'] !== '' && $feature['reason'] !== '')
            ->values()
            ->all();

        if (count($normalized) < 3 || count($normalized) > 8) {
            return [];
        }

        return $normalized;
    }

    public function normalizeStored(mixed $features): ?array
    {
        $normalized = $this->fromAiResponse($features);

        if ($normalized !== []) {
            return $normalized;
        }

        if (is_array($features) && count($features) === 1) {
            $failed = $this->normalizeItem($features[0]);

            if ($failed['feature'] === self::FAILURE_MESSAGE) {
                return [$failed];
            }
        }

        return null;
    }

    private function normalizeItem(mixed $feature): array
    {
        if (! is_array($feature)) {
            return ['feature' => '', 'reason' => ''];
        }

        return [
            'feature' => $this->limit($feature['feature'] ?? '', 160),
            'reason' => $this->limit($feature['reason'] ?? '', 500),
        ];
    }

    private function limit(mixed $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }
}
