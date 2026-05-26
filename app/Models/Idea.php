<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Idea extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'target_user' => 'array',
            'core_features' => 'array',
            'mvp_scope' => 'array',
            'action_tasks' => 'array',
        ];
    }

    /**
     * Each idea belongs to the user who created it.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
