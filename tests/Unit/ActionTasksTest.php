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
