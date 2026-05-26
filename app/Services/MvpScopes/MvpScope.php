<?php

namespace App\Services\MvpScopes;

use Illuminate\Support\Str;

class MvpScope
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const GROUPS = ['must_have', 'nice_to_have', 'later'];

    private const FALLBACK_SCOPE = [
        'must_have' => [
            'User can create an idea',
            'User can generate a simple checklist',
            'User can edit checklist items',
            'User can mark items as complete',
        ],
        'nice_to_have' => [
            'User can organize ideas by status',
            'User can refine generated roadmap sections',
        ],
        'later' => [
            'Collaboration and sharing',
            'Integrations with project management tools',
        ],
    ];

    public function fallback(): array
    {
        return self::FALLBACK_SCOPE;
    }

    public function failed(): array
    {
        return [
            'must_have' => [self::FAILURE_MESSAGE],
            'nice_to_have' => [self::FAILURE_MESSAGE],
            'later' => [self::FAILURE_MESSAGE],
        ];
    }

    public function fromAiResponse(mixed $scope): array
    {
        if (! is_array($scope)) {
            return [];
        }

        $normalized = [];

        foreach (self::GROUPS as $group) {
            if (! isset($scope[$group]) || ! is_array($scope[$group])) {
                return [];
            }

            $items = collect($scope[$group])
                ->map(fn (mixed $item): string => $this->limit($item))
                ->filter(fn (string $item): bool => $item !== '')
                ->values()
                ->all();

            if ($items === []) {
                return [];
            }

            $normalized[$group] = $items;
        }

        return $normalized;
    }

    public function normalizeStored(mixed $scope): ?array
    {
        $normalized = $this->fromAiResponse($scope);

        return $normalized === [] ? null : $normalized;
    }

    private function limit(mixed $value): string
    {
        return Str::limit(trim((string) $value), 200, '');
    }
}
