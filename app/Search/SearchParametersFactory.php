<?php

namespace App\Search;

use Elastic\ScoutDriver\Factories\SearchParametersFactory as BaseFactory;
use Laravel\Scout\Builder;
use stdClass;

class SearchParametersFactory extends BaseFactory
{
    protected function makeQuery(Builder $builder): array
    {
        if (empty($builder->query)) {
            return ['bool' => ['must' => ['match_all' => new stdClass]]];
        }

        return [
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'query' => $builder->query,
                            'type' => 'bool_prefix',
                            'fields' => ['title^3', 'description'],
                            'operator' => 'and',
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                    [
                        'wildcard' => [
                            'tags' => [
                                'value' => '*'.$this->wildcardSafe($builder->query).'*',
                            ],
                        ],
                    ],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * `tags` is mapped as `keyword` (see elastic/migrations), not analyzed `text`, so it's
     * matched separately from title/description via a substring wildcard instead of being
     * tokenized into the same AND-matched query. This is what keeps compound tags like
     * "machine-learning" from cross-matching documents that merely have "machine" and
     * "learning" as two unrelated, separate tags. Spaces are folded to hyphens so a query
     * like "machine learning" still finds the "machine-learning" tag.
     */
    private function wildcardSafe(string $query): string
    {
        $normalized = str_replace(' ', '-', mb_strtolower($query));

        return addcslashes($normalized, '*?\\');
    }
}
