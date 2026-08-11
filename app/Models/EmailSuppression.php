<?php

namespace App\Models;

use App\Enums\SuppressionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['email', 'type', 'subtype'])]
class EmailSuppression extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SuppressionType::class,
        ];
    }

    /**
     * Compared case-insensitively on purpose. SES reports bounced recipients
     * using the casing of the original message, so a hard bounce for
     * "John.Doe@Example.com" must still suppress a user registered as
     * "john.doe@example.com" — otherwise we keep mailing a dead address and
     * damage the sending domain's reputation.
     */
    public static function isSuppressed(string $email): bool
    {
        return static::query()
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->exists();
    }
}
