<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(): Course
    {
        return Course::create([
            'title' => 'Python for Beginners',
            'slug' => 'python-for-beginners',
            'description' => 'Learn Python from scratch',
            'tags' => ['python'],
        ]);
    }

    private function makeConversation(User $user, Course $course, ?Video $video = null): Conversation
    {
        return Conversation::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'video_id' => $video?->id,
        ]);
    }

    public function test_a_conversation_belongs_to_a_user_and_a_course(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourse();

        $conversation = $this->makeConversation($user, $course);

        $this->assertTrue($conversation->user->is($user));
        $this->assertTrue($conversation->course->is($course));
        $this->assertNull($conversation->video_id, 'video_id is a forward seam and stays null in v1');
    }

    /**
     * The load-or-create guarantee. A plain UNIQUE(user_id, course_id, video_id)
     * would NOT catch this: SQL treats NULLs as distinct, so every v1 row (which
     * always has video_id null) would slip past it. The partial index is what
     * makes this pass.
     */
    public function test_a_user_cannot_have_two_threads_on_the_same_course(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourse();

        $this->makeConversation($user, $course);

        $this->expectException(QueryException::class);
        $this->makeConversation($user, $course);
    }

    public function test_different_users_may_each_have_a_thread_on_the_same_course(): void
    {
        $course = $this->makeCourse();

        $this->makeConversation(User::factory()->create(), $course);
        $this->makeConversation(User::factory()->create(), $course);

        $this->assertSame(2, Conversation::count());
    }

    /**
     * Proves the forward seam works: once per-lesson threads ship, a user may
     * hold one course-level thread plus one per video, with no migration.
     */
    public function test_per_lesson_threads_coexist_with_the_course_thread(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourse();
        $one = Video::create(['course_id' => $course->id, 'title' => 'Loops']);
        $two = Video::create(['course_id' => $course->id, 'title' => 'Functions']);

        $this->makeConversation($user, $course);
        $this->makeConversation($user, $course, $one);
        $this->makeConversation($user, $course, $two);

        $this->assertSame(3, Conversation::count());

        $this->expectException(QueryException::class);
        $this->makeConversation($user, $course, $one);
    }

    public function test_messages_record_token_accounting_and_default_to_zero(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, $this->makeCourse());

        $question = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'What is a for loop?',
        ]);

        $answer = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'A for loop repeats a block of code.',
            'input_tokens' => 1750,
            'output_tokens' => 500,
            'cache_read_tokens' => 0,
            'model' => 'claude-haiku-4-5',
            'stop_reason' => 'end_turn',
        ]);

        $this->assertSame(0, $question->fresh()->input_tokens);
        $this->assertSame(0, $question->fresh()->cache_creation_tokens);
        $this->assertSame(1750, $answer->fresh()->input_tokens);
        $this->assertSame('end_turn', $answer->fresh()->stop_reason);
        $this->assertCount(2, $conversation->fresh()->messages);
    }

    public function test_deleting_a_conversation_deletes_its_messages(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, $this->makeCourse());

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);

        $conversation->delete();

        $this->assertSame(0, Message::count());
    }

    public function test_deleting_a_user_deletes_their_conversations_and_messages(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, $this->makeCourse());

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);

        $user->delete();

        $this->assertSame(0, Conversation::count());
        $this->assertSame(0, Message::count());
    }
}
