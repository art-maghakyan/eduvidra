<?php

namespace Tests\Feature;

use App\Enums\RbacRole;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\ChatClient;
use App\Services\Ai\FakeChatClient;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Step 8 — abuse controls on the tutor endpoint.
 */
class AiChatSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->app->instance(ChatClient::class, tap(new FakeChatClient, function (FakeChatClient $fake) {
            $fake->delayMicroseconds = 0;
        }));
    }

    private function makeCourse(): Course
    {
        return Course::create([
            'title' => 'Python for Beginners',
            'slug' => 'python-for-beginners',
            'description' => 'Learn Python from scratch',
            'tags' => ['python'],
        ]);
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole(RbacRole::STUDENT->value);

        return $student;
    }

    /**
     * Each request is drained, which releases the in-flight lock — otherwise
     * the second request would be refused by that lock rather than reaching
     * the rate limiter, and this would test the wrong guard.
     */
    private function ask(User $student, Course $course, string $message)
    {
        $response = $this->actingAs($student)
            ->post("/courses/{$course->slug}/chat", ['message' => $message]);

        if ($response->isOk()) {
            $response->streamedContent();
        }

        return $response;
    }

    public function test_the_burst_rate_limit_trips_on_the_sixth_request_in_a_minute(): void
    {
        $course = $this->makeCourse();
        $student = $this->student();

        for ($i = 1; $i <= 5; $i++) {
            $this->ask($student, $course, "Question {$i}")->assertOk();
        }

        $this->ask($student, $course, 'Six')
            ->assertStatus(429)
            ->assertJsonPath('message', 'You are sending questions faster than the tutor can answer them. Please wait a moment and try again.');

        // Rejected by middleware, so nothing was persisted and no token spent.
        $this->assertSame(5, Message::where('role', 'user')->count());
    }

    public function test_the_rate_limit_is_keyed_by_user_not_ip(): void
    {
        $course = $this->makeCourse();
        $heavy = $this->student();

        for ($i = 1; $i <= 5; $i++) {
            $this->ask($heavy, $course, "Question {$i}");
        }

        $this->ask($heavy, $course, 'Over')->assertStatus(429);

        // Same test client, same IP. Students share NATs, so one heavy user
        // must not consume a classmate's allowance.
        $this->ask($this->student(), $course, 'Mine')->assertOk();
    }

    public function test_a_second_stream_is_refused_while_one_is_still_open(): void
    {
        $course = $this->makeCourse();
        $student = $this->student();

        // Stand in for a stream already in flight. An open stream holds an
        // FPM worker, and the pool is small enough that a few of them would
        // take down the whole site.
        $held = Cache::lock("ai-chat:in-flight:{$student->id}", 60);
        $this->assertTrue($held->get());

        $this->actingAs($student)
            ->post("/courses/{$course->slug}/chat", ['message' => 'Second one'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'The tutor is still answering your last question. Please wait for it to finish.');

        $this->assertSame(0, Message::count());

        $held->release();
    }

    public function test_the_lock_is_released_once_the_reply_finishes(): void
    {
        $course = $this->makeCourse();
        $student = $this->student();

        $this->ask($student, $course, 'First')->assertOk();

        // Freed at the end of the stream rather than left to expire, so the
        // student can ask again immediately.
        $lock = Cache::lock("ai-chat:in-flight:{$student->id}", 60);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_one_students_stream_does_not_block_another(): void
    {
        $course = $this->makeCourse();
        $mine = $this->student();

        $held = Cache::lock("ai-chat:in-flight:{$mine->id}", 60);
        $held->get();

        $this->ask($this->student(), $course, 'Theirs')->assertOk();

        $held->release();
    }
}
