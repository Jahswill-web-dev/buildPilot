<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateIdeaRoadmap;
use App\Models\Idea;
use App\Services\ActionPhases\ActionPhases;
use App\Services\ActionTasks\ActionTasks;
use App\Services\Checklists\ChecklistItems;
use App\Services\CoreFeatures\CoreFeatures;
use App\Services\DesiredOutcomes\DesiredOutcome;
use App\Services\MvpScopes\MvpScope;
use App\Services\ProblemStatements\ProblemStatement;
use App\Services\TargetUsers\TargetUserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function __construct(
        private readonly ActionPhases $actionPhases,
        private readonly ActionTasks $actionTasks,
        private readonly ChecklistItems $checklistItems,
        private readonly CoreFeatures $coreFeatures,
        private readonly DesiredOutcome $desiredOutcome,
        private readonly MvpScope $mvpScope,
        private readonly ProblemStatement $problemStatement,
        private readonly TargetUserProfile $targetUserProfile,
    ) {
    }

    public function index(Request $request): Response
    {
        $ideas = $request->user()
            ->ideas()
            ->latest()
            ->get()
            ->map(fn (Idea $idea): array => $this->serializeIdeaSummary($idea));

        return Inertia::render('Ideas/Index', [
            'ideas' => $ideas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $idea = $request->user()->ideas()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'action_phases' => $this->actionPhases->fallback(),
            'action_tasks' => $this->actionTasks->fallback(),
            'checklist' => [],
            'state' => 'generating',
        ]);

        GenerateIdeaRoadmap::dispatch($idea->id);

        return redirect()->route('home')->with('success', 'Roadmap generation started.');
    }

    public function show(Idea $idea): Response
    {
        $this->authorizeOwner($idea);

        return Inertia::render('Ideas/Show', [
            'idea' => $this->serializeIdea($idea),
        ]);
    }

    public function update(Request $request, Idea $idea): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'target_user' => ['sometimes', 'required', 'array:user_type,main_problem,current_workaround,why_they_care'],
            'target_user.user_type' => ['required_with:target_user', 'string', 'max:500'],
            'target_user.main_problem' => ['required_with:target_user', 'string', 'max:500'],
            'target_user.current_workaround' => ['required_with:target_user', 'string', 'max:500'],
            'target_user.why_they_care' => ['required_with:target_user', 'string', 'max:500'],
            'problem_statement' => ['sometimes', 'required', 'string', 'max:800'],
            'desired_outcome' => ['sometimes', 'required', 'string', 'max:800'],
            'core_features' => ['sometimes', 'required', 'array', 'min:1'],
            'core_features.*.feature' => ['required_with:core_features', 'string', 'max:160'],
            'core_features.*.reason' => ['required_with:core_features', 'string', 'max:500'],
            'mvp_scope' => ['sometimes', 'required', 'array:must_have,nice_to_have,later'],
            'mvp_scope.must_have' => ['required_with:mvp_scope', 'array'],
            'mvp_scope.must_have.*' => ['required', 'string', 'max:200'],
            'mvp_scope.nice_to_have' => ['required_with:mvp_scope', 'array'],
            'mvp_scope.nice_to_have.*' => ['required', 'string', 'max:200'],
            'mvp_scope.later' => ['required_with:mvp_scope', 'array'],
            'mvp_scope.later.*' => ['required', 'string', 'max:200'],
        ]);

        $idea->update($validated);

        return redirect()->route('ideas.show', $idea)->with('success', 'Idea updated.');
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $idea->delete();

        return redirect()->route('home')->with('success', 'Idea deleted.');
    }

    private function authorizeOwner(Idea $idea): void
    {
        abort_unless($idea->user_id === auth()->id(), 403);
    }

    private function serializeIdeaSummary(Idea $idea): array
    {
        return [
            'id' => $idea->id,
            'name' => $idea->name,
            'description' => $idea->description,
            'state' => $idea->state,
            'createdAt' => $idea->created_at?->toISOString(),
            'createdDate' => $idea->created_at?->format('M j, Y'),
            'createdRelative' => $idea->created_at?->diffForHumans(),
        ];
    }

    private function serializeIdea(Idea $idea): array
    {
        return [
            ...$this->serializeIdeaSummary($idea),
            'targetUser' => $this->targetUserProfile->normalizeStored($idea->target_user),
            'problemStatement' => $this->problemStatement->normalizeStored($idea->problem_statement),
            'desiredOutcome' => $this->desiredOutcome->normalizeStored($idea->desired_outcome),
            'coreFeatures' => $this->coreFeatures->normalizeStored($idea->core_features),
            'mvpScope' => $this->mvpScope->normalizeStored($idea->mvp_scope),
            'actionPhases' => $this->actionPhases->normalizeStored($idea->action_phases),
            'actionTasks' => $this->actionTasks->normalizeStored($idea->action_tasks),
            'checklist' => $this->checklistItems->normalizeStored($idea->checklist),
        ];
    }
}
