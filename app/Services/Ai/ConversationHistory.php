<?php

namespace App\Services\Ai;

use App\Models\Conversation;
use App\Models\Message;

/**
 * Decides how much of a thread to replay.
 *
 * The API is stateless, so every turn re-sends the whole conversation and pays
 * input rate on all of it. Left alone a thread grows ~282 tokens per turn
 * forever, so per-turn cost climbs and thread cost climbs quadratically.
 */
class ConversationHistory
{
    /**
     * Bytes per token used for text we have no measurement for.
     *
     * Deliberately pessimistic. Measured against count_tokens, `strlen/4` came
     * out 1.15x for English but **0.64x for Armenian and 0.59x for Python** —
     * the two things this tutor emits constantly. A cost control that
     * under-counts by 40% is not a cost control, so this errs the other way:
     * roughly 2x over on English, about right on code and non-Latin scripts.
     *
     * It only ever applies to user questions, which the Form Request caps at
     * 2,000 characters. Assistant turns — the bulk of a thread — carry their
     * exact output_tokens from the API.
     */
    private const BYTES_PER_TOKEN = 2.3;

    public function __construct(
        private readonly int $budget,
        private readonly int $floor,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('anthropic.history_token_budget'),
            (int) config('anthropic.history_token_floor'),
        );
    }

    public function forConversation(Conversation $conversation): ReplayedHistory
    {
        $messages = $conversation->messages()
            ->orderBy('id')
            ->get(['id', 'role', 'content', 'output_tokens']);

        $total = $messages->sum(fn (Message $m) => $this->tokensFor($m));

        if ($total <= $this->budget) {
            return new ReplayedHistory(
                $this->shape($messages->all()),
                false,
                $messages->first()?->id,
            );
        }

        // Over budget: keep the newest turns that fit under the floor, not just
        // enough to slip under the budget. Trimming invalidates the prompt
        // cache, so it should happen rarely and cut deep rather than often and
        // shallow.
        $kept = [];
        $running = 0;

        foreach (array_reverse($messages->all()) as $message) {
            $cost = $this->tokensFor($message);

            if ($running + $cost > $this->floor && $kept !== []) {
                break;
            }

            $running += $cost;
            array_unshift($kept, $message);
        }

        // The API requires the first message to be from the user; trimming can
        // easily leave an assistant turn stranded at the front.
        while ($kept !== [] && $kept[0]->role !== 'user') {
            array_shift($kept);
        }

        // Shifting can empty the list — a thread whose newest message is an
        // assistant turn, which the live request path never produces because
        // the question is persisted first, but a background job replaying an
        // old thread does. An empty messages array is a 400 from the API, so
        // fall back to the most recent question.
        if ($kept === []) {
            $lastQuestion = $messages->last(fn (Message $m) => $m->role === 'user');

            $kept = $lastQuestion === null ? [] : [$lastQuestion];
        }

        return new ReplayedHistory(
            $this->shape($kept),
            count($kept) < $messages->count(),
            $kept === [] ? null : $kept[0]->id,
        );
    }

    /**
     * Exact where the API told us, pessimistic where it did not.
     *
     * output_tokens is 0 on a turn persisted as `incomplete` (the student
     * closed the tab mid-reply), so a zero with content present means unknown,
     * not empty.
     */
    private function tokensFor(Message $message): int
    {
        if ($message->role === 'assistant' && $message->output_tokens > 0) {
            return $message->output_tokens;
        }

        return (int) ceil(strlen($message->content) / self::BYTES_PER_TOKEN);
    }

    /**
     * @param  list<Message>  $messages
     * @return list<array{role: string, content: string}>
     */
    private function shape(array $messages): array
    {
        return array_values(array_map(fn (Message $m) => [
            'role' => $m->role,
            'content' => $m->content,
        ], $messages));
    }
}
