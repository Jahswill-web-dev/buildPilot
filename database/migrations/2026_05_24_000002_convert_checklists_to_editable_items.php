<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('ideas')
            ->orderBy('id')
            ->each(function (object $idea): void {
                $checklist = json_decode($idea->checklist ?? '[]', true);

                if (! is_array($checklist)) {
                    $checklist = [];
                }

                $items = collect($checklist)
                    ->map(function (mixed $item): array {
                        if (is_array($item)) {
                            return [
                                'id' => (string) ($item['id'] ?? Str::uuid()),
                                'text' => (string) ($item['text'] ?? ''),
                                'done' => (bool) ($item['done'] ?? false),
                            ];
                        }

                        return [
                            'id' => (string) Str::uuid(),
                            'text' => (string) $item,
                            'done' => false,
                        ];
                    })
                    ->filter(fn (array $item): bool => trim($item['text']) !== '')
                    ->values()
                    ->all();

                DB::table('ideas')
                    ->where('id', $idea->id)
                    ->update(['checklist' => json_encode($items)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ideas')
            ->orderBy('id')
            ->each(function (object $idea): void {
                $checklist = json_decode($idea->checklist ?? '[]', true);

                if (! is_array($checklist)) {
                    $checklist = [];
                }

                $items = collect($checklist)
                    ->map(fn (mixed $item): string => is_array($item) ? (string) ($item['text'] ?? '') : (string) $item)
                    ->filter(fn (string $item): bool => trim($item) !== '')
                    ->values()
                    ->all();

                DB::table('ideas')
                    ->where('id', $idea->id)
                    ->update(['checklist' => json_encode($items)]);
            });
    }
};
