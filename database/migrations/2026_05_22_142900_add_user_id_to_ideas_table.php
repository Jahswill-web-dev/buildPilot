<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add user_id foreign key to the ideas table.
     * Ideas are now owned by a specific user.
     */
    public function up(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migration — drop the foreign key and column.
     */
    public function down(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class);
            $table->dropColumn('user_id');
        });
    }
};
