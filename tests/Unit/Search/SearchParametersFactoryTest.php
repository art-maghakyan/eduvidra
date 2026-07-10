<?php

namespace Tests\Unit\Search;

use App\Search\SearchParametersFactory;
use Laravel\Scout\Builder;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Pure unit test for the ES query our custom factory builds.
 *
 * The Feature suite runs Scout on the `collection` driver, which never calls this class,
 * so the query shape (bool_prefix multi_match + the separate keyword `tags` wildcard) has
 * no other coverage. `makeQuery` only reads `$builder->query`, so we pass a plain stdClass
 * as the model and avoid booting Eloquent/the app entirely.
 */
class SearchParametersFactoryTest extends TestCase
{
    /**
     * Expose the protected makeQuery() and feed it a query string.
     */
    private function queryFor(string $q): array
    {
        $factory = new class extends SearchParametersFactory
        {
            public function build(Builder $builder): array
            {
                return $this->makeQuery($builder);
            }
        };

        return $factory->build(new Builder(new stdClass, $q));
    }

    public function test_empty_query_matches_all(): void
    {
        $query = $this->queryFor('');

        $this->assertArrayHasKey('match_all', $query['bool']['must']);
        $this->assertInstanceOf(stdClass::class, $query['bool']['must']['match_all']);
        $this->assertArrayNotHasKey('should', $query['bool']);
    }

    public function test_non_empty_query_uses_should_with_two_clauses(): void
    {
        $query = $this->queryFor('python');

        $this->assertCount(2, $query['bool']['should']);
        $this->assertSame(1, $query['bool']['minimum_should_match']);
    }

    public function test_multi_match_clause_targets_title_and_description(): void
    {
        $multiMatch = $this->queryFor('python')['bool']['should'][0]['multi_match'];

        $this->assertSame('python', $multiMatch['query']);
        $this->assertSame('bool_prefix', $multiMatch['type']);
        $this->assertSame(['title^3', 'description'], $multiMatch['fields']);
        $this->assertSame('and', $multiMatch['operator']);
        $this->assertSame('AUTO', $multiMatch['fuzziness']);
    }

    public function test_multi_match_keeps_the_raw_query_verbatim(): void
    {
        // The analyzed title/description match must NOT fold spaces — only the tags clause does.
        $multiMatch = $this->queryFor('Machine Learning')['bool']['should'][0]['multi_match'];

        $this->assertSame('Machine Learning', $multiMatch['query']);
    }

    public function test_tags_clause_is_a_lowercased_hyphen_folded_wildcard(): void
    {
        // "Machine Learning" must find the single keyword tag "machine-learning".
        $wildcard = $this->queryFor('Machine Learning')['bool']['should'][1]['wildcard'];

        $this->assertSame('*machine-learning*', $wildcard['tags']['value']);
    }

    public function test_tags_wildcard_escapes_wildcard_metacharacters(): void
    {
        // '*' and '?' are ES wildcard operators; a user typing them must match literally.
        $wildcard = $this->queryFor('a*b?c')['bool']['should'][1]['wildcard'];

        $this->assertSame('*a\*b\?c*', $wildcard['tags']['value']);
    }
}
