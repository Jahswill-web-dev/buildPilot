<?php

use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\DesiredOutcomes\DesiredOutcome;
use App\Services\MvpScopes\MvpScope;
use App\Services\ProblemStatements\ProblemStatement;
use App\Services\TargetUsers\TargetUserProfile;

function aiChecklistPayload(int $count = 7): array
{
    return collect(range(1, $count))
        ->map(fn (int $number): array => [
            'title' => "Step {$number}",
            'description' => "Do useful thing {$number}.",
        ])
        ->all();
}

test('ai checklist payloads are normalized with ids and completion state', function () {
    $items = (new ChecklistItems)->fromAiResponse(aiChecklistPayload());

    expect($items)->toHaveCount(7)
        ->and($items[0])->toHaveKeys(['id', 'title', 'description', 'done'])
        ->and($items[0]['title'])->toBe('Step 1')
        ->and($items[0]['description'])->toBe('Do useful thing 1.')
        ->and($items[0]['done'])->toBeFalse();
});

test('ai checklist payloads must contain exactly seven valid items', function (mixed $payload) {
    expect((new ChecklistItems)->fromAiResponse($payload))->toBe([]);
})->with([
    'malformed json shape' => ['not-json'],
    'too few items' => [aiChecklistPayload(6)],
    'too many items' => [aiChecklistPayload(8)],
    'missing field' => [[
        ...array_slice(aiChecklistPayload(), 0, 6),
        ['title' => 'Missing description'],
    ]],
]);

test('stored checklist normalization preserves new items and supports legacy text items', function () {
    $items = (new ChecklistItems)->normalizeStored([
        [
            'id' => 'new-1',
            'title' => 'New title',
            'description' => 'New description',
            'done' => true,
        ],
        [
            'id' => 'legacy-1',
            'text' => 'Legacy text',
            'done' => false,
        ],
    ]);

    expect($items)->toHaveCount(2)
        ->and($items[0]['title'])->toBe('New title')
        ->and($items[0]['description'])->toBe('New description')
        ->and($items[0]['done'])->toBeTrue()
        ->and($items[1]['title'])->toBe('Legacy text')
        ->and($items[1]['description'])->toBe('');
});

test('target user profiles are normalized when all required fields are present', function () {
    $profile = (new TargetUserProfile)->fromAiResponse([
        'user_type' => ' Busy freelancers ',
        'main_problem' => 'They lose time managing client follow-ups.',
        'current_workaround' => 'They use spreadsheets and reminders.',
        'why_they_care' => 'They can save time and avoid missed work.',
    ]);

    expect($profile)->toBe([
        'user_type' => 'Busy freelancers',
        'main_problem' => 'They lose time managing client follow-ups.',
        'current_workaround' => 'They use spreadsheets and reminders.',
        'why_they_care' => 'They can save time and avoid missed work.',
    ]);
});

test('target user profiles reject missing or empty fields', function (mixed $payload) {
    expect((new TargetUserProfile)->fromAiResponse($payload))->toBe([]);
})->with([
    'not an array' => ['not-json'],
    'missing field' => [[
        'user_type' => 'Freelancers',
        'main_problem' => 'Lost time',
        'current_workaround' => 'Spreadsheets',
    ]],
    'empty field' => [[
        'user_type' => 'Freelancers',
        'main_problem' => '',
        'current_workaround' => 'Spreadsheets',
        'why_they_care' => 'Save time',
    ]],
]);

test('problem statements are normalized from ai responses', function () {
    $statement = (new ProblemStatement)->fromAiResponse(' Solo founders have ideas but struggle to define a focused MVP. ');

    expect($statement)->toBe('Solo founders have ideas but struggle to define a focused MVP.');
});

test('empty problem statements are rejected', function () {
    expect((new ProblemStatement)->fromAiResponse('   '))->toBe('');
});

test('desired outcomes are normalized from ai responses', function () {
    $outcome = (new DesiredOutcome)->fromAiResponse(' The user should know what to validate, build, and launch first. ');

    expect($outcome)->toBe('The user should know what to validate, build, and launch first.');
});

test('empty desired outcomes are rejected', function () {
    expect((new DesiredOutcome)->fromAiResponse('   '))->toBe('');
});

test('core features are normalized from ai responses', function () {
    $features = (new CoreFeatures)->fromAiResponse([
        ['feature' => ' Idea capture ', 'reason' => ' Users need to save the idea. '],
        ['feature' => 'Roadmap generation', 'reason' => 'The idea needs a plan.'],
        ['feature' => 'Progress tracking', 'reason' => 'Users need to see completion.'],
    ]);

    expect($features)->toHaveCount(3)
        ->and($features[0])->toBe([
            'feature' => 'Idea capture',
            'reason' => 'Users need to save the idea.',
        ]);
});

test('core features reject malformed payloads', function (mixed $payload) {
    expect((new CoreFeatures)->fromAiResponse($payload))->toBe([]);
})->with([
    'not an array' => ['not-json'],
    'too few' => [[
        ['feature' => 'One', 'reason' => 'Reason one'],
        ['feature' => 'Two', 'reason' => 'Reason two'],
    ]],
    'missing reason' => [[
        ['feature' => 'One', 'reason' => 'Reason one'],
        ['feature' => 'Two', 'reason' => 'Reason two'],
        ['feature' => 'Three'],
    ]],
]);

test('mvp scope is normalized from ai responses', function () {
    $scope = (new MvpScope)->fromAiResponse([
        'must_have' => [' Create an idea ', 'Generate a roadmap'],
        'nice_to_have' => ['Export roadmap'],
        'later' => ['Team collaboration'],
    ]);

    expect($scope)->toBe([
        'must_have' => ['Create an idea', 'Generate a roadmap'],
        'nice_to_have' => ['Export roadmap'],
        'later' => ['Team collaboration'],
    ]);
});

test('mvp scope rejects malformed payloads', function (mixed $payload) {
    expect((new MvpScope)->fromAiResponse($payload))->toBe([]);
})->with([
    'not an array' => ['not-json'],
    'missing group' => [[
        'must_have' => ['Create an idea'],
        'nice_to_have' => ['Export roadmap'],
    ]],
    'empty group' => [[
        'must_have' => [],
        'nice_to_have' => ['Export roadmap'],
        'later' => ['Team collaboration'],
    ]],
]);
