<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const GENERIC_CHECKLIST = [
        'Clarify the problem this idea solves.',
        'Identify who the idea is for.',
        'List the smallest useful version.',
        'Decide the first three actions to build or validate it.',
        'Define what success looks like.',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->json('checklist')->nullable()->after('description');
        });

        DB::table('ideas')
            ->orderBy('id')
            ->each(function (object $idea): void {
                DB::table('ideas')
                    ->where('id', $idea->id)
                    ->update([
                        'name' => Str::limit($idea->description, 80, ''),
                        'checklist' => json_encode(self::GENERIC_CHECKLIST),
                    ]);
            });

        // Creation validation keeps new records populated; leaving the columns nullable
        // avoids database-driver-specific column alteration issues during migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->dropColumn(['name', 'checklist']);
        });
    }
};
