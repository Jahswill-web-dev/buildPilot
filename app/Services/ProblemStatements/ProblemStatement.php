<?php

namespace App\Services\ProblemStatements;

use Illuminate\Support\Str;

class ProblemStatement
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const FALLBACK_STATEMENT = 'Solo founders often have rough startup ideas but struggle to turn them into a specific customer pain, clear MVP scope, feature priorities, and launch steps.';

    public function fallback(): string
    {
        return self::FALLBACK_STATEMENT;
    }

    public function failed(): string
    {
        return self::FAILURE_MESSAGE;
    }

    public function fromAiResponse(mixed $statement): string
    {
        $normalized = Str::limit(trim((string) $statement), 800, '');

        return $normalized === '' ? '' : $normalized;
    }

    public function normalizeStored(mixed $statement): ?string
    {
        $normalized = $this->fromAiResponse($statement);

        return $normalized === '' ? null : $normalized;
    }
}
