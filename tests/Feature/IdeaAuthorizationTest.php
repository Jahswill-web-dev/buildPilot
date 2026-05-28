<?php

use App\Jobs\GenerateIdeaRoadmap;
use App\Models\Idea;
use App\Models\User;
use App\Services\ActionTasks\ActionTasks;
use App\Services\Ai\PhaseTaskGenerator;
use App\Services\Ai\RoadmapGenerator;
use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\MvpScopes\MvpScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function editableChecklistItem(string $text, bool $done = false, string $id = 'item-1'): array
{
    return [
        'id' => $id,
        'title' => $text,
        'description' => '',
        'done' => $done,
    ];
}

function legacyChecklistItem(string $text, bool $done = false, string $id = 'item-1'): array
{
    return [
        'id' => $id,
        'text' => $text,
        'done' => $done,
    ];
}

function generatedChecklist(): array
{
    return collect(range(1, 7))
        ->map(fn (int $number): array => [
            'id' => "generated-{$number}",
            'title' => "Generated item {$number}",
            'description' => "Generated description {$number}",
            'done' => false,
        ])
        ->all();
}

function generatedActionTasks(): array
{
    return [
        [
            'id' => 'task-1',
            'title' => 'Interview five target users',
            'description' => 'Confirm the problem is painful and urgent.',
            'status' => 'pending',
            'phase' => 'Validate',
            'priority' => 'High',
            'category' => 'product',
        ],
        [
            'id' => 'task-2',
            'title' => 'Ship the smallest usable version',
            'description' => 'Build only the core flow.',
            'status' => 'completed',
            'phase' => 'Build',
            'priority' => 'Medium',
            'category' => 'marketing',
        ],
        [
            'id' => 'task-3',
            'title' => 'Draft validation landing page copy',
            'description' => 'Describe the promised outcome for early visitors.',
            'status' => 'pending',
            'phase' => 'Validate',
            'priority' => 'Medium',
            'category' => 'marketing',
        ],
        [
            'id' => 'task-4',
            'title' => 'Compare three direct alternatives',
            'description' => 'Document how people solve this problem today.',
            'status' => 'completed',
            'phase' => 'Validate',
            'priority' => 'Low',
            'category' => 'validation',
        ],
    ];
}

function generatedTargetUser(): array
{
    return [
        'user_type' => 'Solo SaaS founders',
        'main_problem' => 'They struggle to turn rough ideas into concrete execution plans.',
        'current_workaround' => 'They keep scattered notes and manually search for startup advice.',
        'why_they_care' => 'A clear roadmap helps them validate and launch faster with less wasted effort.',
    ];
}

function generatedCoreFeatures(): array
{
    return [
        [
            'feature' => 'Idea capture',
            'reason' => 'Lets founders save the idea they want to validate.',
        ],
        [
            'feature' => 'Roadmap generation',
            'reason' => 'Turns a rough idea into a focused plan.',
        ],
        [
            'feature' => 'Checklist tracking',
            'reason' => 'Helps founders work through the plan step by step.',
        ],
    ];
}

function generatedMvpScope(): array
{
    return [
        'must_have' => ['Create an idea', 'Generate a roadmap', 'Edit checklist items'],
        'nice_to_have' => ['Export the roadmap'],
        'later' => ['Collaborate with teammates'],
    ];
}

function generatedActionPhases(): array
{
    return [
        [
            'title' => 'Validate',
            'name' => 'Validate',
            'slug' => 'validate',
            'description' => 'Confirm the target user, problem, and launch assumptions.',
            'primaryCategory' => 'validation',
            'includedCategories' => ['validation', 'product', 'marketing'],
            'goal' => 'Know whether the idea is worth building.',
            'successCriteria' => 'The riskiest assumptions are confirmed or rejected.',
            'order' => 1,
        ],
        [
            'title' => 'Plan Launch',
            'name' => 'Plan Launch',
            'slug' => 'plan-launch',
            'description' => 'Prepare the positioning and first launch channel.',
            'primaryCategory' => 'marketing',
            'includedCategories' => ['marketing', 'validation'],
            'goal' => 'Get ready to reach first users.',
            'successCriteria' => 'The launch message and audience are ready.',
            'order' => 2,
        ],
    ];
}

function generatedRoadmap(): array
{
    return [
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.',
        'desired_outcome' => 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
        'action_phases' => generatedActionPhases(),
        'checklist' => generatedChecklist(),
    ];
}

test('guests are redirected away from the idea board', function () {
    $this->get('/')->assertRedirect('/login');
    $this->post('/ideas', [
        'name' => 'Build the thing',
        'description' => 'A focused description',
    ])->assertRedirect('/login');
    $this->get('/ideas/1')->assertRedirect('/login');
});

test('authenticated users only see their own ideas', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Idea::create([
        'user_id' => $user->id,
        'name' => 'Private launch plan',
        'description' => 'My private idea',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Other launch plan',
        'description' => 'Someone else idea',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Ideas/Index'));
    $response->assertSee('My private idea');
    $response->assertDontSee('Someone else idea');
});

test('authenticated users can create ideas attached to their account', function () {
    $user = User::factory()->create();
    Queue::fake();

    $response = $this->actingAs($user)->post('/ideas', [
        'name' => 'Account auth',
        'description' => 'Build account auth',
    ]);

    $response->assertRedirect('/');

    $this->assertDatabaseHas('ideas', [
        'user_id' => $user->id,
        'name' => 'Account auth',
        'description' => 'Build account auth',
        'state' => 'generating',
    ]);

    $idea = Idea::where('name', 'Account auth')->firstOrFail();

    expect($idea->target_user)->toBeNull()
        ->and($idea->problem_statement)->toBeNull()
        ->and($idea->desired_outcome)->toBeNull()
        ->and($idea->core_features)->toBeNull()
        ->and($idea->mvp_scope)->toBeNull()
        ->and($idea->checklist)->toBe([])
        ->and($idea->action_phases)->not->toBeEmpty()
        ->and($idea->action_tasks)->toHaveCount(12)
        ->and($idea->action_tasks[0])->toHaveKeys(['id', 'title', 'description', 'status', 'phase', 'phaseSlug', 'priority', 'category']);

    Queue::assertPushed(GenerateIdeaRoadmap::class);
});

test('queued roadmap generation fills generated sections and marks the idea done', function () {
    $user = User::factory()->create();
    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Queued roadmap',
        'description' => 'Generate this in the background.',
        'action_tasks' => [],
        'checklist' => [],
        'state' => 'generating',
    ]);

    $this->app->instance(RoadmapGenerator::class, new class extends RoadmapGenerator
    {
        public function __construct()
        {
        }

        public function generate(string $ideaTitle, string $ideaDescription): array
        {
            return generatedRoadmap();
        }
    });

    app()->call([new GenerateIdeaRoadmap($idea->id), 'handle']);

    $idea->refresh();

    expect($idea->checklist)->toHaveCount(7)
        ->and($idea->checklist[0])->toHaveKeys(['id', 'title', 'description', 'done'])
        ->and($idea->checklist[0]['title'])->toBe('Generated item 1')
        ->and($idea->checklist[0]['done'])->toBeFalse()
        ->and($idea->target_user)->toBe(generatedTargetUser())
        ->and($idea->problem_statement)->toBe('Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.')
        ->and($idea->desired_outcome)->toBe('The user should leave with a clear checklist of what to validate, what to build first, and what to launch.')
        ->and($idea->core_features)->toBe(generatedCoreFeatures())
        ->and($idea->mvp_scope)->toBe(generatedMvpScope())
        ->and($idea->action_phases)->toBe(generatedActionPhases())
        ->and($idea->action_tasks)->toHaveCount(12)
        ->and($idea->state)->toBe('done');
});

test('queued roadmap generation marks the idea failed when generation throws', function () {
    $user = User::factory()->create();
    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Failed roadmap',
        'description' => 'This generation fails.',
        'checklist' => [],
        'state' => 'generating',
    ]);

    $this->app->instance(RoadmapGenerator::class, new class extends RoadmapGenerator
    {
        public function __construct()
        {
        }

        public function generate(string $ideaTitle, string $ideaDescription): array
        {
            throw new RuntimeException('AI failed');
        }
    });

    app()->call([new GenerateIdeaRoadmap($idea->id), 'handle']);

    $idea->refresh();

    expect($idea->state)->toBe('failed')
        ->and($idea->name)->toBe('Failed roadmap');
});

test('authenticated users can view their own idea checklist', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.',
        'desired_outcome' => 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
        'checklist' => [
            legacyChecklistItem('Clarify the problem this idea solves.', false, 'item-1'),
            editableChecklistItem('Identify who the idea is for.', true, 'item-2'),
        ],
        'state' => 'pending',
    ]);

    $response = $this->actingAs($user)->get("/ideas/{$idea->id}");

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Ideas/Show'));
    $response->assertSee('Launch planner');
    $response->assertSee('A tool for planning launches.');
    $response->assertSee('Solo SaaS founders');
    $response->assertSee('rough ideas but struggle');
    $response->assertSee('what to validate');
    $response->assertSee('Roadmap generation');
    $response->assertSee('Create an idea');
    $response->assertSee('Clarify the problem this idea solves.');
    $response->assertSee('Identify who the idea is for.');
});

test('idea detail page serializes the target user profile', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Targeted idea',
        'description' => 'A tool for focused founders.',
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.',
        'desired_outcome' => 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/Show')
            ->where('idea.targetUser.user_type', 'Solo SaaS founders')
            ->where('idea.targetUser.main_problem', 'They struggle to turn rough ideas into concrete execution plans.')
            ->where('idea.targetUser.current_workaround', 'They keep scattered notes and manually search for startup advice.')
            ->where('idea.targetUser.why_they_care', 'A clear roadmap helps them validate and launch faster with less wasted effort.')
            ->where('idea.problemStatement', 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.')
            ->where('idea.desiredOutcome', 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.')
            ->where('idea.coreFeatures.0.feature', 'Idea capture')
            ->where('idea.coreFeatures.0.reason', 'Lets founders save the idea they want to validate.')
            ->where('idea.mvpScope.must_have.0', 'Create an idea')
            ->where('idea.mvpScope.nice_to_have.0', 'Export the roadmap')
            ->where('idea.mvpScope.later.0', 'Collaborate with teammates')
            ->where('idea.actionPhases.0.title', 'Validate the Problem')
            ->where('idea.actionPhases.0.slug', 'validate-the-problem')
            ->where('idea.actionTasks.0.id', 'validate-problem-fit')
            ->where('idea.actionTasks.0.status', ActionTasks::PENDING)
            ->where('idea.actionTasks.0.category', 'validation'));
});

test('idea owners can view the dedicated action tasks page', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/Tasks')
            ->where('idea.name', 'Launch planner')
            ->where('idea.actionPhases.0.title', 'Validate')
            ->where('idea.actionPhases.1.slug', 'plan-launch')
            ->where('idea.actionTasks.0.id', 'task-1')
            ->where('idea.actionTasks.0.status', 'pending')
            ->where('idea.actionTasks.0.category', 'product'));
});

test('idea owners can view a dedicated action task phase page', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/product/validate")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/TaskPhase')
            ->where('category', 'product')
            ->where('phase.name', 'Validate')
            ->where('phase.tasks.0.id', 'task-1'));
});

test('idea owners can view a global action task phase with mixed categories', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/phases/validate")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/TaskPhase')
            ->where('category', null)
            ->where('phase.name', 'Validate')
            ->where('phase.description', 'Confirm the target user, problem, and launch assumptions.')
            ->where('phase.goal', 'Know whether the idea is worth building.')
            ->where('phase.successCriteria', 'The riskiest assumptions are confirmed or rejected.')
            ->where('phase.tasks.0.id', 'task-1')
            ->where('phase.tasks.0.category', 'product')
            ->where('phase.tasks.1.id', 'task-3')
            ->where('phase.tasks.1.category', 'marketing')
            ->where('phase.tasks.2.id', 'task-4')
            ->where('phase.tasks.2.category', 'validation'));
});

test('idea owners can view a generated phase with no matching tasks', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/phases/plan-launch")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/TaskPhase')
            ->where('category', null)
            ->where('phase.name', 'Plan Launch')
            ->where('phase.tasks', []));
});

test('missing global action task phases return not found', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/phases/missing")
        ->assertNotFound();
});

test('idea owners can generate tasks for a roadmap phase', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo SaaS founders need a focused plan.',
        'desired_outcome' => 'A clear launch checklist.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->app->instance(PhaseTaskGenerator::class, new class extends PhaseTaskGenerator
    {
        public function __construct()
        {
        }

        public function generate(Idea $idea, array $phase, array $completedTasks): array
        {
            expect($phase['slug'])->toBe('validate')
                ->and(collect($completedTasks)->pluck('id')->all())->toBe(['task-2', 'task-4']);

            return [
                [
                    'id' => 'validate-1-write-interview-script',
                    'title' => 'Write a five-question interview script',
                    'description' => 'Create exact questions for founder interviews.',
                    'status' => ActionTasks::PENDING,
                    'phase' => 'Validate',
                    'phaseSlug' => 'validate',
                    'priority' => 'High',
                    'category' => 'validation',
                    'taskType' => 'user_interview',
                    'whyItMatters' => 'It keeps validation conversations focused.',
                    'steps' => ['Write five questions', 'Add a closing ask'],
                    'definitionOfDone' => 'The script is ready to send.',
                    'deliverable' => 'Interview script',
                    'estimatedTimeMinutes' => 30,
                    'order' => 1,
                    'interviewQuestions' => ['What do you do today when planning a launch?'],
                    'researchChecklist' => [],
                    'copyExamples' => [],
                    'outreachMessage' => '',
                    'implementationNotes' => [],
                    'acceptanceCriteria' => [],
                    'metricsToTrack' => [],
                ],
            ];
        }
    });

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}/tasks/phases/validate")
        ->post("/ideas/{$idea->id}/tasks/phases/validate/generate")
        ->assertRedirect("/ideas/{$idea->id}/tasks/phases/validate");

    $idea->refresh();

    expect($idea->action_tasks)->toHaveCount(2)
        ->and(collect($idea->action_tasks)->pluck('id')->all())->toBe([
            'task-2',
            'validate-1-write-interview-script',
        ])
        ->and($idea->action_tasks[1])->toMatchArray([
            'phaseSlug' => 'validate',
            'category' => 'validation',
            'taskType' => 'user_interview',
            'deliverable' => 'Interview script',
            'interviewQuestions' => ['What do you do today when planning a launch?'],
        ]);
});

test('phase task generation failure keeps existing tasks unchanged', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->app->instance(PhaseTaskGenerator::class, new class extends PhaseTaskGenerator
    {
        public function __construct()
        {
        }

        public function generate(Idea $idea, array $phase, array $completedTasks): array
        {
            throw new RuntimeException('AI failed');
        }
    });

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}/tasks/phases/validate")
        ->post("/ideas/{$idea->id}/tasks/phases/validate/generate")
        ->assertRedirect("/ideas/{$idea->id}/tasks/phases/validate")
        ->assertSessionHasErrors('tasks');

    $idea->refresh();

    expect($idea->action_tasks)->toBe(generatedActionTasks());
});

test('missing phase task generation returns not found', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->post("/ideas/{$idea->id}/tasks/phases/missing/generate")
        ->assertNotFound();
});

test('users cannot generate phase tasks for another users idea', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Private launch planner',
        'description' => 'A private tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Private item')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->post("/ideas/{$idea->id}/tasks/phases/validate/generate")
        ->assertForbidden();
});

test('idea owners can view a dedicated task detail page', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_phases' => generatedActionPhases(),
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/items/task-1")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/TaskShow')
            ->where('idea.id', $idea->id)
            ->where('task.id', 'task-1')
            ->where('task.title', 'Interview five target users')
            ->where('phase.slug', 'validate'));
});

test('missing task detail pages return not found', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/items/missing-task")
        ->assertNotFound();
});

test('users cannot view another users task detail page', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Private launch planner',
        'description' => 'A private tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Private item')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}/tasks/items/task-1")
        ->assertForbidden();
});

test('idea task status updates persist', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}/tasks")
        ->patch("/ideas/{$idea->id}/tasks/task-1", ['status' => 'completed'])
        ->assertRedirect("/ideas/{$idea->id}/tasks");

    $idea->refresh();
    $task = collect($idea->action_tasks)->firstWhere('id', 'task-1');

    expect($task['status'])->toBe('completed');
});

test('completed idea tasks can be marked pending again', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}/tasks/phases/build")
        ->patch("/ideas/{$idea->id}/tasks/task-2", ['status' => 'pending'])
        ->assertRedirect("/ideas/{$idea->id}/tasks/phases/build");

    $idea->refresh();
    $task = collect($idea->action_tasks)->firstWhere('id', 'task-2');

    expect($task['status'])->toBe('pending');
});

test('fallback action tasks can be completed for older ideas', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Older idea',
        'description' => 'Saved before action tasks existed.',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}/tasks/validate-problem-fit", ['status' => 'completed'])
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();
    $task = collect($idea->action_tasks)->firstWhere('id', 'validate-problem-fit');

    expect($task['status'])->toBe('completed');
});

test('invalid action task updates are rejected', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}/tasks")
        ->patch("/ideas/{$idea->id}/tasks/task-1", ['status' => 'done'])
        ->assertRedirect("/ideas/{$idea->id}/tasks")
        ->assertSessionHasErrors('status');
});

test('missing action task ids return not found', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}/tasks/missing-task", ['status' => 'completed'])
        ->assertNotFound();
});

test('users cannot view or update action tasks owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Protected idea',
        'description' => 'Private detail',
        'action_tasks' => generatedActionTasks(),
        'checklist' => [editableChecklistItem('Private item', false, 'item-1')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)->get("/ideas/{$idea->id}/tasks")->assertForbidden();
    $this->actingAs($user)->get("/ideas/{$idea->id}/tasks/phases/validate")->assertForbidden();
    $this->actingAs($user)->get("/ideas/{$idea->id}/tasks/product/validate")->assertForbidden();
    $this->actingAs($user)->patch("/ideas/{$idea->id}/tasks/task-1", ['status' => 'completed'])->assertForbidden();
});

test('partial generation failures are persisted and visible', function () {
    $user = User::factory()->create();
    Queue::fake();
    $this->app->instance(RoadmapGenerator::class, new class extends RoadmapGenerator
    {
        public function __construct()
        {
        }

        public function generate(string $ideaTitle, string $ideaDescription): array
        {
            return [
                'target_user' => generatedTargetUser(),
                'problem_statement' => 'Solo founders need a focused plan.',
                'desired_outcome' => 'The user should know what to build first.',
                'core_features' => [
                    ['feature' => CoreFeatures::FAILURE_MESSAGE, 'reason' => ''],
                ],
                'mvp_scope' => [
                    'must_have' => [MvpScope::FAILURE_MESSAGE],
                    'nice_to_have' => [MvpScope::FAILURE_MESSAGE],
                    'later' => [MvpScope::FAILURE_MESSAGE],
                ],
                'checklist' => [
                    [
                        'id' => 'failed-checklist',
                        'title' => ChecklistItems::FAILURE_MESSAGE,
                        'description' => '',
                        'done' => false,
                    ],
                ],
            ];
        }
    });

    $this->actingAs($user)->post('/ideas', [
        'name' => 'Partial failure',
        'description' => 'Some generated sections fail.',
    ])->assertRedirect('/');

    $idea = Idea::where('name', 'Partial failure')->firstOrFail();

    expect($idea->state)->toBe('generating')
        ->and($idea->core_features)->toBeNull()
        ->and($idea->mvp_scope)->toBeNull()
        ->and($idea->checklist)->toBe([]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/Index')
            ->where('ideas.0.state', 'generating'));
});

test('older ideas without generated feature sections still render', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Older idea',
        'description' => 'Saved before feature sections existed.',
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo founders need a focused plan.',
        'desired_outcome' => 'The user should know what to build first.',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/Show')
            ->where('idea.coreFeatures', null)
            ->where('idea.mvpScope', null));
});

test('authenticated users can update their idea name and description', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Old name',
        'description' => 'Old description',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", ['name' => 'New name'])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", ['description' => 'New description'])
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();

    expect($idea->name)->toBe('New name')
        ->and($idea->description)->toBe('New description');
});

test('authenticated users can update generated idea sections', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Old problem statement.',
        'desired_outcome' => 'Old desired outcome.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", [
            'target_user' => [
                'user_type' => 'Agency owners',
                'main_problem' => 'They need clearer client onboarding.',
                'current_workaround' => 'They use scattered docs and calls.',
                'why_they_care' => 'A cleaner flow helps them start projects faster.',
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", ['problem_statement' => 'Agency owners need a repeatable way to onboard new clients.'])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", ['desired_outcome' => 'The user should leave with a reusable onboarding flow.'])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", [
            'core_features' => [
                ['feature' => 'Client intake', 'reason' => 'Collects the details needed to start well.'],
                ['feature' => 'Roadmap generation', 'reason' => 'Turns onboarding context into a plan.'],
                ['feature' => 'Checklist tracking', 'reason' => 'Keeps project setup moving.'],
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}", [
            'mvp_scope' => [
                'must_have' => ['Capture client details', 'Create onboarding checklist'],
                'nice_to_have' => ['Export onboarding plan'],
                'later' => ['Client portal'],
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();

    expect($idea->target_user['user_type'])->toBe('Agency owners')
        ->and($idea->target_user['main_problem'])->toBe('They need clearer client onboarding.')
        ->and($idea->problem_statement)->toBe('Agency owners need a repeatable way to onboard new clients.')
        ->and($idea->desired_outcome)->toBe('The user should leave with a reusable onboarding flow.')
        ->and($idea->core_features[0])->toBe([
            'feature' => 'Client intake',
            'reason' => 'Collects the details needed to start well.',
        ])
        ->and($idea->mvp_scope['must_have'])->toBe(['Capture client details', 'Create onboarding checklist']);
});

test('authenticated users can add edit delete and toggle checklist items', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'checklist' => [editableChecklistItem('Original item', false, 'item-1')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->post("/ideas/{$idea->id}/checklist-items", ['text' => 'Added item'])
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();
    $addedItem = collect($idea->checklist)->firstWhere('title', 'Added item');

    expect($addedItem)->not->toBeNull()
        ->and($addedItem['done'])->toBeFalse();

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}/checklist-items/item-1", ['text' => 'Updated item'])
        ->assertRedirect("/ideas/{$idea->id}");

    $this->actingAs($user)
        ->patch("/ideas/{$idea->id}/checklist-items/item-1", ['done' => '1'])
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();
    $updatedItem = collect($idea->checklist)->firstWhere('id', 'item-1');

    expect($updatedItem['title'])->toBe('Updated item')
        ->and($updatedItem['done'])->toBeTrue();

    $this->actingAs($user)
        ->delete("/ideas/{$idea->id}/checklist-items/{$addedItem['id']}")
        ->assertRedirect("/ideas/{$idea->id}");

    $idea->refresh();

    expect(collect($idea->checklist)->firstWhere('id', $addedItem['id']))->toBeNull();
});

test('users cannot update ideas or checklist items owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Protected idea',
        'description' => 'Private detail',
        'checklist' => [editableChecklistItem('Private item', false, 'item-1')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)->patch("/ideas/{$idea->id}", ['name' => 'Stolen'])->assertForbidden();
    $this->actingAs($user)->post("/ideas/{$idea->id}/checklist-items", ['text' => 'Stolen'])->assertForbidden();
    $this->actingAs($user)->patch("/ideas/{$idea->id}/checklist-items/item-1", ['text' => 'Stolen'])->assertForbidden();
    $this->actingAs($user)->delete("/ideas/{$idea->id}/checklist-items/item-1")->assertForbidden();
});

test('invalid idea and checklist updates fail validation', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Launch planner',
        'description' => 'A tool for planning launches.',
        'checklist' => [editableChecklistItem('Original item', false, 'item-1')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}", ['name' => ''])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('name');

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}", [
            'target_user' => [
                'user_type' => '',
                'main_problem' => 'They need a focused plan.',
                'current_workaround' => 'They use notes.',
                'why_they_care' => 'They want to move faster.',
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('target_user.user_type');

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}", ['problem_statement' => ''])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('problem_statement');

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}", [
            'core_features' => [
                ['feature' => '', 'reason' => 'Missing a feature title.'],
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('core_features.0.feature');

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->patch("/ideas/{$idea->id}", [
            'mvp_scope' => [
                'must_have' => [''],
                'nice_to_have' => ['Export roadmap'],
                'later' => ['Team collaboration'],
            ],
        ])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('mvp_scope.must_have.0');

    $this->actingAs($user)
        ->from("/ideas/{$idea->id}")
        ->post("/ideas/{$idea->id}/checklist-items", ['text' => ''])
        ->assertRedirect("/ideas/{$idea->id}")
        ->assertSessionHasErrors('text');
});

test('checklist migration converts string items to editable objects', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Migrated idea',
        'description' => 'Migrated description',
        'checklist' => [],
        'state' => 'pending',
    ]);

    DB::table('ideas')
        ->where('id', $idea->id)
        ->update(['checklist' => json_encode(['First legacy item', 'Second legacy item'])]);

    $migration = require database_path('migrations/2026_05_24_000002_convert_checklists_to_editable_items.php');
    $migration->up();

    $idea->refresh();

    expect($idea->checklist)->toHaveCount(2)
        ->and($idea->checklist[0])->toHaveKeys(['id', 'text', 'done'])
        ->and($idea->checklist[0]['text'])->toBe('First legacy item')
        ->and($idea->checklist[0]['done'])->toBeFalse();
});

test('legacy text checklist items are normalized for the idea page', function () {
    $user = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $user->id,
        'name' => 'Legacy idea',
        'description' => 'A saved idea from the old checklist shape.',
        'checklist' => [legacyChecklistItem('Legacy checklist copy', false, 'legacy-1')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ideas/Show')
            ->where('idea.checklist.0.id', 'legacy-1')
            ->where('idea.checklist.0.title', 'Legacy checklist copy')
            ->where('idea.checklist.0.description', '')
            ->where('idea.checklist.0.done', false));
});

test('users cannot view ideas owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Protected idea',
        'description' => 'Private detail',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)->get("/ideas/{$idea->id}")->assertForbidden();
});

test('users cannot delete ideas owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $idea = Idea::create([
        'user_id' => $otherUser->id,
        'name' => 'Protected idea',
        'description' => 'Protected idea',
        'checklist' => [editableChecklistItem('Clarify the problem this idea solves.')],
        'state' => 'pending',
    ]);

    $this->actingAs($user)->delete("/ideas/{$idea->id}")->assertForbidden();

    $this->assertDatabaseHas('ideas', [
        'id' => $idea->id,
        'description' => 'Protected idea',
    ]);
});
