<?php

use App\Models\Idea;
use App\Models\User;
use App\Services\ActionTasks\ActionTasks;
use App\Services\Ai\RoadmapGenerator;
use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\MvpScopes\MvpScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

function generatedRoadmap(): array
{
    return [
        'target_user' => generatedTargetUser(),
        'problem_statement' => 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.',
        'desired_outcome' => 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.',
        'core_features' => generatedCoreFeatures(),
        'mvp_scope' => generatedMvpScope(),
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

    $response = $this->actingAs($user)->post('/ideas', [
        'name' => 'Account auth',
        'description' => 'Build account auth',
    ]);

    $response->assertRedirect('/');

    $this->assertDatabaseHas('ideas', [
        'user_id' => $user->id,
        'name' => 'Account auth',
        'description' => 'Build account auth',
        'problem_statement' => 'Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.',
        'desired_outcome' => 'The user should leave with a clear checklist of what to validate, what to build first, and what to launch.',
        'state' => 'pending',
    ]);

    $idea = Idea::where('name', 'Account auth')->firstOrFail();

    expect($idea->checklist)->toHaveCount(7)
        ->and($idea->checklist[0])->toHaveKeys(['id', 'title', 'description', 'done'])
        ->and($idea->checklist[0]['title'])->toBe('Generated item 1')
        ->and($idea->checklist[0]['description'])->toBe('Generated description 1')
        ->and($idea->checklist[0]['done'])->toBeFalse()
        ->and($idea->target_user)->toBe(generatedTargetUser())
        ->and($idea->problem_statement)->toBe('Solo SaaS founders often have rough ideas but struggle to turn them into concrete MVP scopes, feature priorities, and launch steps.')
        ->and($idea->desired_outcome)->toBe('The user should leave with a clear checklist of what to validate, what to build first, and what to launch.')
        ->and($idea->core_features)->toBe(generatedCoreFeatures())
        ->and($idea->mvp_scope)->toBe(generatedMvpScope())
        ->and($idea->action_tasks)->toHaveCount(12)
        ->and($idea->action_tasks[0])->toHaveKeys(['id', 'title', 'description', 'status', 'phase', 'phaseSlug', 'priority', 'category']);
});

test('idea creation falls back to local roadmap when ai is unavailable', function () {
    $user = User::factory()->create();

    config(['services.openai.api_key' => null]);

    $this->actingAs($user)->post('/ideas', [
        'name' => 'Fallback plan',
        'description' => 'Generate without an API key.',
    ])->assertRedirect('/');

    $idea = Idea::where('name', 'Fallback plan')->firstOrFail();

    expect($idea->checklist)->toHaveCount(7)
        ->and($idea->checklist[0])->toHaveKeys(['id', 'title', 'description', 'done'])
        ->and($idea->checklist[0]['title'])->toBe('Clarify the problem')
        ->and($idea->checklist[0]['description'])->not->toBeEmpty()
        ->and($idea->checklist[0]['done'])->toBeFalse()
        ->and($idea->target_user)->toHaveKeys(['user_type', 'main_problem', 'current_workaround', 'why_they_care'])
        ->and($idea->target_user['user_type'])->not->toBeEmpty()
        ->and($idea->problem_statement)->not->toBeEmpty()
        ->and($idea->desired_outcome)->not->toBeEmpty();
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
    $this->actingAs($user)->patch("/ideas/{$idea->id}/tasks/task-1", ['status' => 'completed'])->assertForbidden();
});

test('partial generation failures are persisted and visible', function () {
    $user = User::factory()->create();
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

    expect($idea->core_features[0]['feature'])->toBe(CoreFeatures::FAILURE_MESSAGE)
        ->and($idea->mvp_scope['must_have'])->toBe([MvpScope::FAILURE_MESSAGE])
        ->and($idea->checklist[0]['title'])->toBe(ChecklistItems::FAILURE_MESSAGE);

    $this->actingAs($user)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee(CoreFeatures::FAILURE_MESSAGE);
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
