<?php

namespace Tests\Feature;

use App\Enums\RbacRole;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\ChatClient;
use App\Services\Ai\ConversationHistory;
use App\Services\Ai\FakeChatClient;
use App\Services\Ai\TutorPrompt;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 7 — the daily quota and the replayed-history budget.
 */
class AiChatBudgetTest extends TestCase
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

    private function ask(User $student, Course $course, string $message)
    {
        return $this->actingAs($student)
            ->post("/courses/{$course->slug}/chat", ['message' => $message]);
    }

    public function test_the_question_over_the_daily_limit_is_refused_with_429(): void
    {
        config(['anthropic.daily_message_limit' => 3]);

        $course = $this->makeCourse();
        $student = $this->student();

        for ($i = 1; $i <= 3; $i++) {
            $this->ask($student, $course, "Question {$i}")->streamedContent();
        }

        $response = $this->ask($student, $course, 'One too many');

        $response->assertStatus(429)
            ->assertJsonPath('message', 'You have used all 3 tutor questions for today. You can ask again after midnight.');

        $this->assertGreaterThan(0, $response->json('retry_after'));
        $this->assertNotEmpty($response->headers->get('Retry-After'));

        // Refused before anything is persisted and before a token is spent.
        $this->assertSame(3, Message::where('role', 'user')->count());
        $this->assertSame(0, Message::where('content', 'One too many')->count());
    }

    public function test_the_quota_counts_across_courses_so_it_cannot_be_sidestepped(): void
    {
        config(['anthropic.daily_message_limit' => 2]);

        $python = $this->makeCourse();
        $rust = Course::create(['title' => 'Rust', 'slug' => 'rust', 'description' => 'x', 'tags' => []]);
        $student = $this->student();

        $this->ask($student, $python, 'One')->streamedContent();
        $this->ask($student, $python, 'Two')->streamedContent();

        $this->ask($student, $rust, 'Three on a different course')->assertStatus(429);
    }

    public function test_one_students_usage_does_not_consume_anothers_quota(): void
    {
        config(['anthropic.daily_message_limit' => 1]);

        $course = $this->makeCourse();

        $this->ask($this->student(), $course, 'Mine')->streamedContent();

        // A fresh student still has their full allowance.
        $this->ask($this->student(), $course, 'Theirs')->assertOk();
    }

    public function test_yesterdays_questions_do_not_count_against_today(): void
    {
        config(['anthropic.daily_message_limit' => 1]);

        $course = $this->makeCourse();
        $student = $this->student();

        $conversation = Conversation::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'video_id' => null,
        ]);

        Message::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Yesterday'])
            ->forceFill(['created_at' => now()->subDay()])->save();

        $this->ask($student, $course, 'Today')->assertOk();
    }

    public function test_a_short_thread_is_replayed_whole(): void
    {
        $conversation = $this->threadWith(3);

        [$history, $trimmed] = (new ConversationHistory(12000, 6000))->forConversation($conversation);

        $this->assertFalse($trimmed);
        $this->assertCount(6, $history);
    }

    public function test_an_oversized_thread_is_trimmed_to_the_floor_and_keeps_the_newest_turns(): void
    {
        // 40 turns at 500 output tokens each is far past a 12,000 budget.
        $conversation = $this->threadWith(40, outputTokens: 500);

        [$history, $trimmed] = (new ConversationHistory(12000, 6000))->forConversation($conversation);

        $this->assertTrue($trimmed);
        $this->assertLessThan(80, count($history));

        // Cut to the floor, not merely under the budget — trimming invalidates
        // the prompt cache, so it must be rare and deep.
        $this->assertLessThanOrEqual(6000 + 500, $this->tokensIn($history));

        // The newest turn always survives; the oldest is what goes.
        $this->assertStringStartsWith('Question 40', $history[count($history) - 2]['content']);
        $this->assertNotSame('Question 1', $history[0]['content']);
    }

    public function test_a_trimmed_replay_still_starts_with_a_user_turn(): void
    {
        // Long questions are what make the cut land mid-turn: walking back from
        // the newest message, the assistant fits and the big question before it
        // does not, stranding that assistant at the front. Short questions never
        // reproduce it, so this fixture uses the 2,000-character maximum.
        $conversation = $this->threadWith(40, outputTokens: 500, questionChars: 2000);

        [$history] = (new ConversationHistory(12000, 6000))->forConversation($conversation);

        // The API rejects a messages array that opens with an assistant turn.
        $this->assertSame('user', $history[0]['role']);
    }

    public function test_the_model_is_told_when_history_was_dropped(): void
    {
        $course = $this->makeCourse();

        $this->assertStringNotContainsString(TutorPrompt::TRIMMED_NOTICE, TutorPrompt::forCourse($course));
        $this->assertStringContainsString(TutorPrompt::TRIMMED_NOTICE, TutorPrompt::forCourse($course, true));
    }

    private function threadWith(int $turns, int $outputTokens = 50, int $questionChars = 0): Conversation
    {
        $course = $this->makeCourse();
        $student = $this->student();

        $conversation = Conversation::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'video_id' => null,
        ]);

        for ($i = 1; $i <= $turns; $i++) {
            $question = "Question {$i}";
            if ($questionChars > 0) {
                $question = str_pad($question, $questionChars, ' and more detail');
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $question,
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => "Answer {$i}",
                'output_tokens' => $outputTokens,
            ]);
        }

        return $conversation;
    }

    private function tokensIn(array $history): int
    {
        $total = 0;
        foreach ($history as $message) {
            $total += $message['role'] === 'assistant' ? 500 : (int) ceil(strlen($message['content']) / 2.3);
        }

        return $total;
    }
}
