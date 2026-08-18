<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One turn in a Conversation — either the student's question or Claude's reply.
 *
 * Token columns are populated from App\Services\Ai\ChatResult. Total input for
 * a turn is input_tokens + cache_creation_tokens + cache_read_tokens.
 */
#[Fillable([
    'conversation_id',
    'role',
    'content',
    'input_tokens',
    'output_tokens',
    'cache_creation_tokens',
    'cache_read_tokens',
    'model',
    'stop_reason',
])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cache_creation_tokens' => 'integer',
            'cache_read_tokens' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
