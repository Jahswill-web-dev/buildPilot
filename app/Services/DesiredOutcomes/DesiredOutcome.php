<?php

namespace App\Services\DesiredOutcomes;

use Illuminate\Support\Str;

class DesiredOutcome
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const FALLBACK_OUTCOME = 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch so they can move from idea to execution with confidence.';

    public function fallback(): string
    {
        return self::FALLBACK_OUTCOME;
    }

    public function failed(): string
    {
        return self::FAILURE_MESSAGE;
    }

    public function fromAiResponse(mixed $outcome): string
    {
        $normalized = Str::limit(trim((string) $outcome), 800, '');

        return $normalized === '' ? '' : $normalized;
    }

    public function normalizeStored(mixed $outcome): ?string
    {
        $normalized = $this->fromAiResponse($outcome);

        return $normalized === '' ? null : $normalized;
    }
}
