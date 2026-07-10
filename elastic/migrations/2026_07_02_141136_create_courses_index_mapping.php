<?php

declare(strict_types=1);

use Elastic\Adapter\Indices\Mapping;
use Elastic\Adapter\Indices\Settings;
use Elastic\Migrations\Facades\Index;
use Elastic\Migrations\MigrationInterface;

final class CreateCoursesIndexMapping implements MigrationInterface
{
    /**
     * Run the migration.
     *
     * `tags` must NOT go through the default text analyzer: it's a closed vocabulary of
     * category labels, not prose. The standard analyzer splits on hyphens (e.g.
     * "machine-learning" -> ["machine", "learning"]), which made unrelated docs tagged
     * e.g. "machine-tools" + "learning-path" match a search for "machine-learning".
     * Mapping it as `keyword` (with a lowercase normalizer) makes tags match as whole
     * values instead of independently-searchable word fragments.
     */
    public function up(): void
    {
        Index::dropIfExists('courses');

        Index::create('courses', function (Mapping $mapping, Settings $settings) {
            $settings->analysis([
                'normalizer' => [
                    'lowercase_normalizer' => [
                        'type' => 'custom',
                        'filter' => ['lowercase'],
                    ],
                ],
            ]);

            $mapping->keyword('tags', [
                'normalizer' => 'lowercase_normalizer',
            ]);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::dropIfExists('courses');
    }
}
