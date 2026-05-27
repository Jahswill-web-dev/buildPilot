<?php

use App\Services\ActionPhases\ActionPhases;

test('ai action phases are normalized into ordered app shape', function () {
    $phases = (new ActionPhases)->fromAiResponse([
        [
            'title' => 'Prepare the MVP Launch',
            'description' => 'Get the launch message and channel ready.',
            'primary_category' => 'marketing',
            'included_categories' => ['marketing', 'validation'],
            'goal' => 'Reach the first users.',
            'success_criteria' => 'Launch message is ready.',
            'order' => 2,
        ],
        [
            'title' => 'Validate the Problem',
            'description' => 'Confirm the pain and target user.',
            'primary_category' => 'validation',
            'included_categories' => ['validation', 'product'],
            'goal' => 'Know whether the problem matters.',
            'success_criteria' => 'The riskiest assumptions are documented.',
            'order' => 1,
        ],
    ]);

    expect($phases)->toHaveCount(2)
        ->and($phases[0])->toMatchArray([
            'title' => 'Validate the Problem',
            'name' => 'Validate the Problem',
            'slug' => 'validate-the-problem',
            'primaryCategory' => 'validation',
            'includedCategories' => ['validation', 'product'],
            'successCriteria' => 'The riskiest assumptions are documented.',
            'order' => 1,
        ])
        ->and($phases[1]['slug'])->toBe('prepare-the-mvp-launch');
});

test('action phases normalize invalid categories to product', function () {
    $phases = (new ActionPhases)->fromAiResponse([
        [
            'title' => 'Shape the MVP',
            'description' => 'Define the first usable version.',
            'primary_category' => 'sales',
            'included_categories' => ['sales', 'marketing'],
            'goal' => 'Lock scope.',
            'success_criteria' => 'Scope is clear.',
            'order' => 1,
        ],
    ]);

    expect($phases[0]['primaryCategory'])->toBe('product')
        ->and($phases[0]['includedCategories'])->toBe(['product', 'marketing']);
});

test('empty stored action phases fall back to roadmap phases', function () {
    $phases = (new ActionPhases)->normalizeStored(null);

    expect($phases)->toHaveCount(5)
        ->and($phases[0]['title'])->toBe('Validate the Problem')
        ->and($phases[0]['slug'])->toBe('validate-the-problem');
});
