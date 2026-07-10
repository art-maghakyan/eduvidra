<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CourseShowTest extends TestCase
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

    public function test_course_page_is_accessible_without_authentication(): void
    {
        $this->makeCourse();

        $this->get('/courses/python-for-beginners')->assertOk();
    }

    public function test_course_page_is_resolved_by_slug_and_renders_details(): void
    {
        $course = $this->makeCourse();
        Video::create(['course_id' => $course->id, 'title' => 'Setting up Python']);
        Video::create(['course_id' => $course->id, 'title' => 'Variables and Types']);

        $this->get('/courses/python-for-beginners')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Courses/Show')
                ->where('course.title', 'Python for Beginners')
                ->where('course.slug', 'python-for-beginners')
                ->where('course.tags', ['python', 'programming'])
                ->has('course.videos', 2)
                ->where('course.videos.0.title', 'Setting up Python')
            );
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/courses/does-not-exist')->assertNotFound();
    }
}
