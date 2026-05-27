<?php

use App\Services\Ai\RoadmapGenerator;
use App\Services\ActionPhases\ActionPhases;
use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\DesiredOutcomes\DesiredOutcome;
use App\Services\MvpScopes\MvpScope;
use App\Services\ProblemStatements\ProblemStatement;
use App\Services\TargetUsers\TargetUserProfile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

function roadmapGenerator(): RoadmapGenerator
{
    return new RoadmapGenerator(
        new ActionPhases,
        new ChecklistItems,
        new CoreFeatures,
        new DesiredOutcome,
        new MvpScope,
        new ProblemStatement,
        new TargetUserProfile,
    );
}

function writeRoadmapScript(string $contents): string
{
    $path = storage_path('framework/testing/roadmap-generator-test.mjs');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, $contents);

    return $path;
}

test('roadmap generator returns normalized ai payloads from the node bridge', function () {
    config([
        'services.openai.api_key' => 'test-key',
        'services.openai.roadmap_script' => writeRoadmapScript(<<<'JS'
const input = JSON.parse(await new Promise((resolve) => {
  let data = '';
  process.stdin.on('data', (chunk) => data += chunk);
  process.stdin.on('end', () => resolve(data));
}));

process.stdout.write(JSON.stringify({
  target_user: {
    user_type: `Founders building ${input.idea_title}`,
    main_problem: 'They need a focused validation plan.',
    current_workaround: 'They collect notes manually.',
    why_they_care: 'A practical roadmap helps them launch faster.'
  },
  problem_statement: 'Founders need a concrete way to turn a rough idea into launch steps.',
  desired_outcome: 'The founder should know what to validate, build, and launch first.',
  core_features: [
    { feature: 'Idea capture', reason: 'Lets founders save the idea.' },
    { feature: 'Roadmap generation', reason: 'Turns the idea into a plan.' },
    { feature: 'Progress tracking', reason: 'Shows what is done next.' }
  ],
  mvp_scope: {
    must_have: ['Create an idea', 'Generate a roadmap'],
    nice_to_have: ['Export roadmap'],
    later: ['Team collaboration']
  },
  phases: [
    {
      title: 'Validate the Problem',
      description: 'Confirm the audience and pain.',
      primary_category: 'validation',
      included_categories: ['validation', 'product'],
      goal: 'Know if the problem is worth solving.',
      success_criteria: 'Five useful conversations are complete.',
      order: 1
    },
    {
      title: 'Shape the MVP',
      description: 'Define the smallest useful version.',
      primary_category: 'product',
      included_categories: ['product', 'validation'],
      goal: 'Lock the first version scope.',
      success_criteria: 'Must-have scope is clear.',
      order: 2
    },
    {
      title: 'Build the MVP',
      description: 'Create the first usable product.',
      primary_category: 'product',
      included_categories: ['product'],
      goal: 'Ship the must-have flow.',
      success_criteria: 'The product works end to end.',
      order: 3
    },
    {
      title: 'Prepare the Launch',
      description: 'Package the offer and channel.',
      primary_category: 'marketing',
      included_categories: ['marketing'],
      goal: 'Reach the first users.',
      success_criteria: 'Launch message is ready.',
      order: 4
    },
    {
      title: 'Improve From Feedback',
      description: 'Use early learning to prioritize next steps.',
      primary_category: 'validation',
      included_categories: ['validation', 'product', 'marketing'],
      goal: 'Learn what to improve.',
      success_criteria: 'Next move is prioritized.',
      order: 5
    }
  ],
  checklist: Array.from({ length: 7 }, (_, index) => ({
    title: `Step ${index + 1}`,
    description: `Do practical task ${index + 1}.`
  }))
}));
JS),
    ]);

    $roadmap = roadmapGenerator()->generate('Launch planner', 'A planning tool for indie founders.');

    expect($roadmap['target_user']['user_type'])->toBe('Founders building Launch planner')
        ->and($roadmap['problem_statement'])->toBe('Founders need a concrete way to turn a rough idea into launch steps.')
        ->and($roadmap['desired_outcome'])->toBe('The founder should know what to validate, build, and launch first.')
        ->and($roadmap['core_features'])->toHaveCount(3)
        ->and($roadmap['core_features'][0])->toBe([
            'feature' => 'Idea capture',
            'reason' => 'Lets founders save the idea.',
        ])
        ->and($roadmap['mvp_scope']['must_have'])->toBe(['Create an idea', 'Generate a roadmap'])
        ->and($roadmap['action_phases'])->toHaveCount(5)
        ->and($roadmap['action_phases'][0])->toMatchArray([
            'title' => 'Validate the Problem',
            'slug' => 'validate-the-problem',
            'primaryCategory' => 'validation',
            'includedCategories' => ['validation', 'product'],
        ])
        ->and($roadmap['checklist'])->toHaveCount(7)
        ->and($roadmap['checklist'][0])->toHaveKeys(['id', 'title', 'description', 'done'])
        ->and($roadmap['checklist'][0]['title'])->toBe('Step 1')
        ->and($roadmap['checklist'][0]['done'])->toBeFalse();
});

test('roadmap generator stores section failure placeholders for malformed staged output', function () {
    config([
        'services.openai.api_key' => 'test-key',
        'services.openai.roadmap_script' => writeRoadmapScript(<<<'JS'
process.stdout.write(JSON.stringify({
  target_user: {
    user_type: 'Solo founders',
    main_problem: 'They need a focused launch plan.',
    current_workaround: 'They keep notes manually.',
    why_they_care: 'They want to move faster.'
  },
  problem_statement: 'Solo founders need a focused way to turn ideas into launch plans.',
  desired_outcome: 'The founder should know what to validate and build first.',
  core_features: [{ feature: 'Missing reason' }],
  mvp_scope: { must_have: [], nice_to_have: [], later: [] },
  phases: null,
  checklist: [{ title: 'Too few', description: 'Only one item.' }]
}));
JS),
    ]);

    $roadmap = roadmapGenerator()->generate('Partial idea', 'This should preserve successful sections.');

    expect($roadmap['target_user']['user_type'])->toBe('Solo founders')
        ->and($roadmap['problem_statement'])->toBe('Solo founders need a focused way to turn ideas into launch plans.')
        ->and($roadmap['desired_outcome'])->toBe('The founder should know what to validate and build first.')
        ->and($roadmap['core_features'][0]['feature'])->toBe(CoreFeatures::FAILURE_MESSAGE)
        ->and($roadmap['mvp_scope']['must_have'])->toBe([MvpScope::FAILURE_MESSAGE])
        ->and($roadmap['action_phases'][0]['title'])->toBe('Validate the Problem')
        ->and($roadmap['checklist'])->toHaveCount(1)
        ->and($roadmap['checklist'][0]['title'])->toBe(ChecklistItems::FAILURE_MESSAGE);
});

test('roadmap generator logs stage errors returned by the node bridge', function () {
    Log::spy();

    config([
        'services.openai.api_key' => 'test-key',
        'services.openai.roadmap_script' => writeRoadmapScript(<<<'JS'
process.stdout.write(JSON.stringify({
  target_user: {
    user_type: 'Solo founders',
    main_problem: 'They need a focused launch plan.',
    current_workaround: 'They keep notes manually.',
    why_they_care: 'They want to move faster.'
  },
  problem_statement: 'Solo founders need a focused way to turn ideas into launch plans.',
  desired_outcome: 'The founder should know what to validate and build first.',
  core_features: null,
  mvp_scope: {
    must_have: ['Create an idea'],
    nice_to_have: ['Export roadmap'],
    later: ['Team collaboration']
  },
  phases: null,
  checklist: Array.from({ length: 7 }, (_, index) => ({
    title: `Step ${index + 1}`,
    description: `Do practical task ${index + 1}.`
  })),
  stage_errors: {
    core_features: 'Model returned invalid core feature JSON',
    phases: 'Model returned invalid phase JSON'
  }
}));
JS),
    ]);

    roadmapGenerator()->generate('Logged partial failure', 'This should log the failed stage.');

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('AI roadmap generation completed with failed stages.', [
            'failed_stages' => ['core_features', 'phases'],
            'stage_errors' => [
                'core_features' => 'Model returned invalid core feature JSON',
                'phases' => 'Model returned invalid phase JSON',
            ],
        ]);
});

test('roadmap generator falls back when the node bridge fails', function () {
    config([
        'services.openai.api_key' => 'test-key',
        'services.openai.roadmap_script' => writeRoadmapScript(<<<'JS'
process.stderr.write('bridge failed');
process.exit(1);
JS),
    ]);

    $roadmap = roadmapGenerator()->generate('Fallback idea', 'This should use local defaults.');

    expect($roadmap['target_user'])->toHaveKeys(['user_type', 'main_problem', 'current_workaround', 'why_they_care'])
        ->and($roadmap['problem_statement'])->not->toBeEmpty()
        ->and($roadmap['desired_outcome'])->not->toBeEmpty()
        ->and($roadmap['core_features'])->toHaveCount(4)
        ->and($roadmap['mvp_scope']['must_have'])->not->toBeEmpty()
        ->and($roadmap['action_phases'])->toHaveCount(5)
        ->and($roadmap['checklist'])->toHaveCount(7)
        ->and($roadmap['checklist'][0]['title'])->toBe('Clarify the problem');
});
