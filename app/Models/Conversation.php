<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One AI tutor thread, scoped to a (user, course) pair in v1.
 *
 * video_id is a forward seam and is always null until a lesson page exists —
 * see docs/ai-chat-tutor.md.
 */
#[Fillable(['user_id', 'course_id', 'video_id', 'title', 'summary', 'summarised_through_message_id', 'last_message_at'])]
class Conversation extends Model
{
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
