<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Services\Checklists\ChecklistItems;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChecklistItemController extends Controller
{
    public function __construct(private readonly ChecklistItems $checklistItems)
    {
    }

    public function store(Request $request, Idea $idea): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $items = $this->checklistItems->normalizeStored($idea->checklist);
        $items[] = [
            'id' => (string) Str::uuid(),
            'title' => $validated['text'],
            'description' => '',
            'done' => false,
        ];

        $idea->update(['checklist' => $items]);

        return redirect()->route('ideas.show', $idea)->with('success', 'Checklist item added.');
    }

    public function update(Request $request, Idea $idea, string $itemId): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $validated = $request->validate([
            'text' => ['sometimes', 'required', 'string', 'max:500'],
            'done' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('text', $validated)) {
            $validated['title'] = $validated['text'];
            unset($validated['text']);
        }

        if (array_key_exists('done', $validated)) {
            $validated['done'] = $request->boolean('done');
        }

        $found = false;
        $items = collect($this->checklistItems->normalizeStored($idea->checklist))
            ->map(function (array $item) use ($itemId, $validated, &$found): array {
                if ($item['id'] !== $itemId) {
                    return $item;
                }

                $found = true;

                return [
                    ...$item,
                    ...array_intersect_key($validated, array_flip(['title', 'done'])),
                ];
            })
            ->values()
            ->all();

        abort_unless($found, 404);

        $idea->update(['checklist' => $items]);

        return redirect()->route('ideas.show', $idea)->with('success', 'Checklist item updated.');
    }

    public function destroy(Idea $idea, string $itemId): RedirectResponse
    {
        $this->authorizeOwner($idea);

        $items = $this->checklistItems->normalizeStored($idea->checklist);
        $remainingItems = collect($items)
            ->reject(fn (array $item): bool => $item['id'] === $itemId)
            ->values()
            ->all();

        abort_if(count($remainingItems) === count($items), 404);

        $idea->update(['checklist' => $remainingItems]);

        return redirect()->route('ideas.show', $idea)->with('success', 'Checklist item deleted.');
    }

    private function authorizeOwner(Idea $idea): void
    {
        abort_unless($idea->user_id === auth()->id(), 403);
    }
}
