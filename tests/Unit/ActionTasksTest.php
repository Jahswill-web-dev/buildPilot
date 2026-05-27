<?php

use App\Services\ActionTasks\ActionTasks;

test('fallback action tasks use the expected task shape', function () {
    $tasks = (new ActionTasks)->fallback();

    expect($tasks)->toHaveCount(12)
        ->and($tasks[0])->toHaveKeys(['id', 'title', 'description', 'status', 'phase', 'phaseSlug', 'priority', 'category'])
        ->and($tasks[0]['id'])->toBe('validate-problem-fit')
        ->and($tasks[0]['status'])->toBe(ActionTasks::PENDING)
        ->and($tasks[0]['phase'])->toBe('Validate')
        ->and($tasks[0]['phaseSlug'])->toBe('validate')
        ->and($tasks[0]['priority'])->toBe('High')
        ->and($tasks[0]['category'])->toBe('validation')
        ->and(collect($tasks)->where('category', 'validation')->count())->toBeGreaterThan(0)
        ->and(collect($tasks)->where('category', 'product')->count())->toBeGreaterThan(0)
        ->and(collect($tasks)->where('category', 'marketing')->count())->toBeGreaterThan(0);
});

test('stored action tasks are normalized and invalid values are defaulted', function () {
    $tasks = (new ActionTasks)->normalizeStored([
        [
            'id' => 'task-1',
            'title' => '  Interview customers  ',
            'description' => ' Confirm the pain. ',
            'status' => ActionTasks::COMPLETED,
            'phase' => 'Validate',
            'priority' => 'high',
            'category' => 'marketing',
        ],
        [
            'id' => 'task-2',
            'title' => 'Ship the MVP',
            'description' => 'Release the smallest version.',
            'status' => 'done',
            'phase' => 'Build',
            'priority' => 'urgent',
            'category' => 'sales',
        ],
    ]);

    expect($tasks)->toHaveCount(2)
        ->and($tasks[0]['title'])->toBe('Interview customers')
        ->and($tasks[0]['description'])->toBe('Confirm the pain.')
        ->and($tasks[0]['status'])->toBe(ActionTasks::COMPLETED)
        ->and($tasks[0]['phaseSlug'])->toBe('validate')
        ->and($tasks[0]['priority'])->toBe('High')
        ->and($tasks[0]['category'])->toBe('marketing')
        ->and($tasks[1]['status'])->toBe(ActionTasks::PENDING)
        ->and($tasks[1]['priority'])->toBe('Medium')
        ->and($tasks[1]['category'])->toBe('product');
});

test('empty stored action tasks fall back to dummy tasks', function () {
    $tasks = (new ActionTasks)->normalizeStored(null);

    expect($tasks)->toHaveCount(12)
        ->and($tasks[0]['id'])->toBe('validate-problem-fit');
});

test('ai phase tasks are normalized with rich task details', function () {
    $tasks = (new ActionTasks)->fromAiPhaseResponse([
        'tasks' => [
            [
                'title' => 'Map the onboarding database changes',
                'category' => 'product',
                'task_type' => 'implementation',
                'description' => 'Define the tables needed for onboarding.',
                'why_it_matters' => 'It keeps the build focused.',
                'steps' => ['List entities', 'Choose required fields'],
                'definition_of_done' => 'The schema is documented.',
                'deliverable' => 'A schema note.',
                'priority' => 'high',
                'estimated_time_minutes' => 45,
                'order' => 2,
                'interview_questions' => ['What is hard about setup?'],
                'research_checklist' => ['Compare two onboarding flows'],
                'copy_examples' => ['Set up your workspace in five minutes.'],
                'outreach_message' => 'Can I show you a setup flow?',
                'implementation_notes' => ['Use existing users table.'],
                'acceptance_criteria' => ['A new user can save setup details.'],
                'metrics_to_track' => ['Setup completion rate'],
            ],
        ],
    ], [
        'title' => 'Build the First Usable Version',
        'slug' => 'build-the-first-usable-version',
    ], 'build-the-first-usable-version');

    expect($tasks)->toHaveCount(1)
        ->and($tasks[0])->toMatchArray([
            'id' => 'build-the-first-usable-version-2-map-the-onboarding-database-changes',
            'title' => 'Map the onboarding database changes',
            'status' => ActionTasks::PENDING,
            'phase' => 'Build the First Usable Version',
            'phaseSlug' => 'build-the-first-usable-version',
            'priority' => 'High',
            'category' => 'product',
            'taskType' => 'implementation',
            'whyItMatters' => 'It keeps the build focused.',
            'steps' => ['List entities', 'Choose required fields'],
            'definitionOfDone' => 'The schema is documented.',
            'deliverable' => 'A schema note.',
            'estimatedTimeMinutes' => 45,
            'order' => 2,
            'interviewQuestions' => ['What is hard about setup?'],
            'researchChecklist' => ['Compare two onboarding flows'],
            'copyExamples' => ['Set up your workspace in five minutes.'],
            'outreachMessage' => 'Can I show you a setup flow?',
            'implementationNotes' => ['Use existing users table.'],
            'acceptanceCriteria' => ['A new user can save setup details.'],
            'metricsToTrack' => ['Setup completion rate'],
        ]);
});

test('ai phase tasks reject malformed titles and default invalid values', function () {
    $tasks = (new ActionTasks)->fromAiPhaseResponse([
        [
            'title' => '',
            'description' => 'Missing title.',
        ],
        [
            'title' => 'Draft launch positioning',
            'category' => 'sales',
            'task_type' => 'strategy',
            'description' => 'Write positioning for the launch.',
            'priority' => 'urgent',
            'estimated_time_minutes' => 999,
            'order' => 1,
        ],
    ], [
        'title' => 'Plan Launch',
        'slug' => 'plan-launch',
    ], 'plan-launch');

    expect($tasks)->toHaveCount(1)
        ->and($tasks[0]['category'])->toBe('product')
        ->and($tasks[0]['taskType'])->toBe('other')
        ->and($tasks[0]['priority'])->toBe('Medium')
        ->and($tasks[0]['estimatedTimeMinutes'])->toBe(480);
});
