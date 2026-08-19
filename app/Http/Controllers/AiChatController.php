<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\ChatClient;
use App\Services\Ai\ChatResult;
use App\Services\Ai\ConversationHistory;
use App\Services\Ai\TutorPrompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AiChatController extends Controller
{
    /**
     * Step 6: one turn, streamed over SSE.
     *
     * The reply streams over the same ChatClient::stream() the non-streaming
     * round trip used, so there is still one code path to the model rather than
     * two that can drift apart.
     *
     * The conversation is identified by the authenticated user plus the course
     * in the URL — no conversation id is accepted from the client, so there is
     * no id to enumerate and no thread of anyone else's to reach.
     */
    public function stream(SendChatMessageRequest $request, Course $course, ChatClient $chat): StreamedResponse|JsonResponse
    {
        // FPM runs on PHP's built-in 30s default (no php.ini is loaded in the
        // image), which is shorter than a long generation. Stays under nginx's
        // fastcgi_read_timeout so PHP, not nginx, is what gives up first.
        set_time_limit((int) config('anthropic.stream_timeout'));

        $user = $request->user();

        // Checked before anything is persisted and before a single token is
        // spent. Returned as JSON rather than an SSE error event because no
        // stream is opened — there is nothing to stream into.
        if ($overQuota = $this->quotaExceeded($user)) {
            return $overQuota;
        }

        // Race-safe against the partial unique index on (user_id, course_id)
        // where video_id is null. video_id stays null until a lesson page exists.
        $conversation = Conversation::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'video_id' => null,
        ]);

        $question = $request->validated()['message'];

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $question,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        // Built before the response is returned, so a failure assembling the
        // prompt is an ordinary 500 rather than a half-sent stream.
        [$history, $trimmed] = ConversationHistory::fromConfig()->forConversation($conversation);
        $system = TutorPrompt::forCourse($course, $trimmed);

        return response()->eventStream(function () use ($chat, $system, $history, $conversation) {
            $answer = '';
            $result = null;
            $message = null;
            $completed = false;

            try {
                $stream = $chat->stream($system, $history);

                foreach ($stream as $chunk) {
                    $answer .= $chunk;

                    yield new StreamedEvent('delta', self::payload(['text' => $chunk]));
                }

                $result = $stream->getReturn();
                $completed = true;
            } catch (Throwable $e) {
                Log::error('AI tutor stream failed', [
                    'conversation_id' => $conversation->id,
                    'exception' => $e->getMessage(),
                ]);

                // eventStream() wraps this generator in its own catch, calls
                // report() and closes the connection cleanly — the browser would
                // see a successful, silently truncated reply. The failure has to
                // become an event here or it never reaches the student.
                yield new StreamedEvent('error', self::payload([
                    'message' => 'The tutor could not finish answering. Please try again.',
                ]));
            } finally {
                // Also runs when PHP destroys this generator because the student
                // closed the tab mid-reply. The tokens are billed either way, so
                // whatever arrived is persisted rather than thrown away.
                $message = $this->persistAnswer($conversation, $answer, $result);
            }

            if ($completed && $message !== null) {
                yield new StreamedEvent('done', self::payload(['id' => $message->id]));
            }
        }, endStreamWith: null);
    }

    /**
     * The assistant turn, or null when nothing was generated.
     *
     * A null $result means the stream never reached its final event — the
     * student navigated away, or the API failed part-way — so the token counts
     * are unknown and the turn is marked incomplete rather than left looking
     * like a clean answer.
     */
    private function persistAnswer(Conversation $conversation, string $answer, ?ChatResult $result): ?Message
    {
        if ($answer === '') {
            return null;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            // The token columns are NOT NULL DEFAULT 0; the default only
            // applies to an omitted column, so an unknown count is written as 0.
            'input_tokens' => $result?->inputTokens ?? 0,
            'output_tokens' => $result?->outputTokens ?? 0,
            'cache_creation_tokens' => $result?->cacheCreationTokens ?? 0,
            'cache_read_tokens' => $result?->cacheReadTokens ?? 0,
            'model' => $result?->model,
            'stop_reason' => $result?->stopReason ?? 'incomplete',
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    /**
     * One SSE data field.
     *
     * Laravel's eventStream() writes `data: ` followed by the payload verbatim,
     * so a raw newline in the payload would end the event early and split a
     * reply across frames. Claude's chunks contain newlines constantly (lists,
     * code blocks), so every payload is JSON — which escapes them — and never
     * an interpolated string. Invalid UTF-8 is substituted rather than thrown
     * on: a mangled character is better than a dead stream.
     */
    private static function payload(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }

    /**
     * A 429 when the student has used up today's questions, or null.
     *
     * Counts questions rather than conversations, across every course, so the
     * cap cannot be sidestepped by opening a thread on another course.
     */
    private function quotaExceeded(User $user): ?JsonResponse
    {
        $limit = (int) config('anthropic.daily_message_limit');

        $asked = Message::query()
            ->where('role', 'user')
            ->whereIn('conversation_id', Conversation::query()
                ->where('user_id', $user->id)
                ->select('id'))
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($asked < $limit) {
            return null;
        }

        $resetsAt = now()->addDay()->startOfDay();
        $retryAfter = (int) now()->diffInSeconds($resetsAt);

        return response()->json([
            'message' => sprintf(
                'You have used all %d tutor questions for today. You can ask again after midnight.',
                $limit,
            ),
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }
}
