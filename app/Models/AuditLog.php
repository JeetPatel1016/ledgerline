<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Append-only: the database rejects updates and deletes. */
#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'before', 'after'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
