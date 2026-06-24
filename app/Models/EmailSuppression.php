<?php

namespace App\Models;

use App\Enums\SuppressionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'type', 'subtype'])]
class EmailSuppression extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SuppressionType::class,
        ];
    }

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', $email)->exists();
    }
}
