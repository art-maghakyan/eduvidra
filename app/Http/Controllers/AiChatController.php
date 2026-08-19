<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use App\Services\Ai\ChatClient;
use App\Services\Ai\TutorPrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatController extends Controller
{
    /**
     * Step 2/4: one round trip, persisted.
     *
     * Deliberately NOT streaming yet — the generator is drained into a single
     * string here. Step 6 swaps this for an SSE endpoint using the same
     * ChatClient, so no second code path appears.
     */
    public function store(SendChatMessageRequest $request, Course $course, ChatClient $chat): RedirectResponse
    {
        $user = $request->user();

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

        try {
            $stream = $chat->stream(TutorPrompt::forCourse($course), $this->history($conversation));

            $answer = '';
            foreach ($stream as $chunk) {
                $answer .= $chunk;
            }

            $result = $stream->getReturn();
        } catch (Throwable $e) {
            // The student's question stays persisted so they can see what they
            // asked; only the reply is missing.
            Log::error('AI tutor request failed', [
                'conversation_id' => $conversation->id,
                'exception' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'message' => 'The tutor could not answer just now. Please try again.',
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'cache_creation_tokens' => $result->cacheCreationTokens,
            'cache_read_tokens' => $result->cacheReadTokens,
            'model' => $result->model,
            'stop_reason' => $result->stopReason,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return back();
    }

    /**
     * Replayed conversation so far, oldest first.
     *
     * Bounded in practice by the daily message cap. Step 7 adds a token-based
     * ceiling and summarises the dropped prefix.
     *
     * @return list<array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('id')
            ->get(['role', 'content'])
            ->map(fn (Message $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();
    }
}
