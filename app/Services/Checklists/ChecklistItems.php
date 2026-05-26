<?php

namespace App\Services\Checklists;

use Illuminate\Support\Str;

class ChecklistItems
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const FALLBACK_ITEMS = [
        [
            'title' => 'Clarify the problem',
            'description' => 'Write down the exact pain this idea solves and why it matters now.',
        ],
        [
            'title' => 'Define the target customer',
            'description' => 'Identify the first group of people most likely to need this solution.',
        ],
        [
            'title' => 'Validate demand',
            'description' => 'Talk to potential users or share a simple landing page before building.',
        ],
        [
            'title' => 'Scope the MVP',
            'description' => 'Choose the smallest useful version that proves the core value.',
        ],
        [
            'title' => 'Plan the build',
            'description' => 'Break the MVP into a short list of must-have features and tasks.',
        ],
        [
            'title' => 'Prepare the launch',
            'description' => 'Pick a launch channel and create the first message for your audience.',
        ],
        [
            'title' => 'Measure next steps',
            'description' => 'Decide which signals will show whether to continue, adjust, or stop.',
        ],
    ];

    public function fallback(): array
    {
        return $this->withIds(self::FALLBACK_ITEMS);
    }

    public function failed(): array
    {
        return $this->withIds([
            [
                'title' => self::FAILURE_MESSAGE,
                'description' => '',
            ],
        ]);
    }

    public function fromAiResponse(mixed $items): array
    {
        if (! is_array($items) || count($items) !== 7) {
            return [];
        }

        $normalized = collect($items)
            ->map(fn (mixed $item): array => $this->normalizeAiItem($item))
            ->filter(fn (array $item): bool => $item['title'] !== '' && $item['description'] !== '')
            ->values()
            ->all();

        if (count($normalized) !== 7) {
            return [];
        }

        return $this->withIds($normalized);
    }

    public function normalizeStored(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn (mixed $item): array => $this->normalizeStoredItem($item))
            ->filter(fn (array $item): bool => $item['title'] !== '')
            ->values()
            ->all();
    }

    private function withIds(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): array => [
                'id' => (string) Str::uuid(),
                'title' => $this->limit($item['title'] ?? '', 120),
                'description' => $this->limit($item['description'] ?? '', 500),
                'done' => false,
            ])
            ->all();
    }

    private function normalizeAiItem(mixed $item): array
    {
        if (! is_array($item)) {
            return ['title' => '', 'description' => ''];
        }

        return [
            'title' => $this->limit($item['title'] ?? '', 120),
            'description' => $this->limit($item['description'] ?? '', 500),
        ];
    }

    private function normalizeStoredItem(mixed $item): array
    {
        if (is_array($item)) {
            $title = $item['title'] ?? $item['text'] ?? '';

            return [
                'id' => (string) ($item['id'] ?? Str::uuid()),
                'title' => $this->limit($title, 120),
                'description' => $this->limit($item['description'] ?? '', 500),
                'done' => (bool) ($item['done'] ?? false),
            ];
        }

        return [
            'id' => (string) Str::uuid(),
            'title' => $this->limit($item, 120),
            'description' => '',
            'done' => false,
        ];
    }

    private function limit(mixed $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }
}
