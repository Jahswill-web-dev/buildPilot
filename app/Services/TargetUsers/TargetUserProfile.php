<?php

namespace App\Services\TargetUsers;

use Illuminate\Support\Str;

class TargetUserProfile
{
    public const FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

    private const FALLBACK_PROFILE = [
        'user_type' => 'Indie founders or solo operators exploring this idea',
        'main_problem' => 'They need to understand whether the idea solves a real and urgent problem before spending time building.',
        'current_workaround' => 'They rely on notes, guesses, scattered research, or manual conversations to shape the opportunity.',
        'why_they_care' => 'A clearer target user helps them validate faster, build a smaller MVP, and avoid wasting effort on the wrong audience.',
    ];

    public function fallback(): array
    {
        return self::FALLBACK_PROFILE;
    }

    public function failed(): array
    {
        return [
            'user_type' => self::FAILURE_MESSAGE,
            'main_problem' => self::FAILURE_MESSAGE,
            'current_workaround' => self::FAILURE_MESSAGE,
            'why_they_care' => self::FAILURE_MESSAGE,
        ];
    }

    public function fromAiResponse(mixed $profile): array
    {
        if (! is_array($profile)) {
            return [];
        }

        $normalized = [
            'user_type' => $this->limit($profile['user_type'] ?? ''),
            'main_problem' => $this->limit($profile['main_problem'] ?? ''),
            'current_workaround' => $this->limit($profile['current_workaround'] ?? ''),
            'why_they_care' => $this->limit($profile['why_they_care'] ?? ''),
        ];

        foreach ($normalized as $value) {
            if ($value === '') {
                return [];
            }
        }

        return $normalized;
    }

    public function normalizeStored(mixed $profile): ?array
    {
        $normalized = $this->fromAiResponse($profile);

        return $normalized === [] ? null : $normalized;
    }

    private function limit(mixed $value): string
    {
        return Str::limit(trim((string) $value), 500, '');
    }
}
