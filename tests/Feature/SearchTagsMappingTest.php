<?php

namespace Tests\Feature;

use App\Models\Course;
use Elastic\Migrations\Facades\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end integration test for the `courses.tags` keyword mapping against a REAL
 * Elasticsearch cluster.
 *
 * The rest of the suite runs Scout on the `collection` driver (in-memory LIKE matching),
 * which cannot reproduce ES analyzer/tokenization behaviour — so the actual bug this
 * mapping fixes (a compound tag like "machine-learning" being split into "machine" +
 * "learning" and cross-matching unrelated docs) has no coverage there.
 *
 * This test is **self-skipping**: if ES is not reachable it is skipped, so local
 * `php artisan test` and the current CI (no ES service) stay green. It only runs when an
 * ES cluster is up at the configured host (e.g. the dev docker stack, or a future CI job
 * with an `elasticsearch` service).
 *
 * Isolation: it points Scout AND elastic-migrations at a `testing_` index prefix, so it
 * operates on `testing_courses` and never touches the real dev `courses`/`videos` indexes.
 */
class SearchTagsMappingTest extends TestCase
{
    use RefreshDatabase;

    private const PREFIX = 'testing_';

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->elasticsearchReachable()) {
            $this->markTestSkipped('Elasticsearch not reachable at '.$this->esHost());
        }

        // Use the real ES engine with synchronous, immediately-searchable indexing, on an
        // isolated index prefix so we never clobber the dev indexes.
        config([
            'scout.driver' => 'elastic',
            'scout.queue' => false,
            'scout.prefix' => self::PREFIX,
            'elastic.migrations.prefixes.index' => self::PREFIX,
            'elastic.scout_driver.refresh_documents' => true,
        ]);

        // Create testing_courses / testing_videos with the mapping under test.
        Artisan::call('elastic:migrate');
    }

    protected function tearDown(): void
    {
        // Only touch ES if we actually set the test up against it (i.e. not skipped).
        if ($this->elasticsearchReachable()) {
            config(['elastic.migrations.prefixes.index' => self::PREFIX]);
            Index::dropIfExists('courses');
        }

        parent::tearDown();
    }

    public function test_tags_index_uses_keyword_mapping_with_lowercase_normalizer(): void
    {
        $mapping = Http::get('http://'.$this->esHost().'/'.self::PREFIX.'courses/_mapping')->json();
        $tags = $mapping[self::PREFIX.'courses']['mappings']['properties']['tags'];

        // If this ever reverts to auto-mapping, `type` becomes `text` — the original bug.
        $this->assertSame('keyword', $tags['type']);
        $this->assertSame('lowercase_normalizer', $tags['normalizer']);
    }

    public function test_compound_tag_matches_only_the_exact_tag_not_split_fragments(): void
    {
        // Titles/descriptions deliberately contain neither "machine" nor "learning", so a
        // match can ONLY come from the tags clause — isolating the mapping behaviour.
        $exact = $this->makeCourse('alpha', ['machine-learning']);
        $decoy = $this->makeCourse('beta', ['machine-tools', 'learning-path']);

        $ids = Course::search('machine learning')->get()->pluck('id');

        // Correct keyword behaviour: the whole-value tag matches, the split fragments don't.
        $this->assertContains($exact->id, $ids->all(), 'Course tagged "machine-learning" should match.');
        $this->assertNotContains($decoy->id, $ids->all(), 'Course tagged "machine-tools"+"learning-path" must NOT match — that was the bug.');
    }

    private function makeCourse(string $key, array $tags): Course
    {
        $course = Course::create([
            'title' => "Course {$key}",
            'slug' => "course-{$key}",
            'description' => 'Neutral description with no overlapping words.',
            'tags' => $tags,
        ]);

        // Force synchronous indexing (refresh_documents => immediately searchable).
        $course->searchable();

        return $course;
    }

    private function esHost(): string
    {
        return config('elastic.client.connections.default.hosts')[0] ?? 'localhost:9200';
    }

    private function elasticsearchReachable(): bool
    {
        [$host, $port] = array_pad(explode(':', $this->esHost()), 2, '9200');

        $conn = @fsockopen($host, (int) $port, $errno, $errstr, 1.0);

        if ($conn === false) {
            return false;
        }

        fclose($conn);

        return true;
    }
}
