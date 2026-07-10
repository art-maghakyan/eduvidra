<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[Fillable(['title', 'slug', 'description', 'tags', 'is_published', 'published_at'])]
class Course extends Model
{
    use HasFactory, Searchable;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * The data indexed for search.
     *
     * ES (dev): tags is indexed as an array so the keyword mapping + tags
     * wildcard in SearchParametersFactory work. The database driver (prod)
     * searches columns with LIKE, and tags is a jsonb column Postgres can't
     * LIKE, so prod search covers title + description only.
     */
    public function toSearchableArray(): array
    {
        $data = [
            'title' => $this->title,
            'description' => $this->description,
        ];

        return config('scout.driver') === 'database'
            ? $data
            : $data + ['tags' => $this->tags];
    }
}
