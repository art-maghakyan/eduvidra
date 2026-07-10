<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $attrs = []): Course
    {
        return Course::create(array_merge([
            'title' => 'Python for Beginners',
            'slug' => 'python-for-beginners',
            'description' => 'Learn Python from scratch',
            'tags' => ['python', 'programming'],
        ], $attrs));
    }

    public function test_endpoint_is_accessible_without_authentication(): void
    {
        $this->getJson('/search?q=python')->assertOk();
    }

    public function test_returns_empty_results_when_nothing_matches(): void
    {
        $this->getJson('/search?q=xyznonexistent')
            ->assertOk()
            ->assertJson(['courses' => []]);
    }

    public function test_returns_matching_courses(): void
    {
        $this->makeCourse();

        $this->getJson('/search?q=Python')
            ->assertOk()
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('courses.0.title', 'Python for Beginners');
    }

    public function test_response_only_contains_courses(): void
    {
        $this->makeCourse();

        $this->getJson('/search?q=Python')
            ->assertOk()
            ->assertJsonStructure(['courses'])
            ->assertJsonMissingPath('videos');
    }

    public function test_validation_rejects_missing_q(): void
    {
        $this->getJson('/search')->assertUnprocessable();
    }

    public function test_validation_rejects_empty_q(): void
    {
        $this->getJson('/search?q=')->assertUnprocessable();
    }

    public function test_validation_rejects_q_exceeding_max_length(): void
    {
        $this->getJson('/search?q='.str_repeat('a', 201))->assertUnprocessable();
    }
}
