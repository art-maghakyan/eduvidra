<?php

namespace App\Services\Ai;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;

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
        private readonly int $gapDays = 0,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('anthropic.history_token_budget'),
            (int) config('anthropic.history_token_floor'),
            (int) config('anthropic.history_gap_days'),
        );
    }

    public function forConversation(Conversation $conversation): ReplayedHistory
    {
        $all = $conversation->messages()
            ->orderBy('id')
            ->get(['id', 'role', 'content', 'output_tokens', 'created_at']);

        // A long silence is a topic boundary, so the replay starts after it.
        // Everything before is left to the summariser rather than re-sent.
        $messages = $this->afterLastIdleGap($all);

        $total = $messages->sum(fn (Message $m) => $this->tokensFor($m));

        if ($total <= $this->budget) {
            return $this->result($messages->all(), $all);
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

        return $this->result($kept, $all);
    }

    /**
     * Drop everything before the most recent silence longer than the gap.
     *
     * A student returning after two months on a different topic gets a tutor
     * still primed on the old one, and there is no way for them to say so:
     * there is exactly one permanent thread per (user, course) and nothing
     * resets it. Cutting the replay at the gap gives them a fresh start without
     * a second conversation row — which would mean changing the partial unique
     * index, and therefore a migration.
     *
     * The dropped turns are not lost. They are exactly what
     * SummariseConversationHistory covers, so the tutor still knows who it is
     * talking to; only the stale detail goes.
     *
     * @param  Collection<int, Message>  $messages
     * @return Collection<int, Message>
     */
    private function afterLastIdleGap(Collection $messages): Collection
    {
        if ($this->gapDays <= 0 || $messages->count() < 2) {
            return $messages;
        }

        $ordered = $messages->values();

        for ($i = $ordered->count() - 1; $i > 0; $i--) {
            $previous = $ordered[$i - 1]->created_at;
            $current = $ordered[$i]->created_at;

            if ($previous === null || $current === null) {
                continue;
            }

            if ($previous->copy()->addDays($this->gapDays)->lt($current)) {
                return $ordered->slice($i)->values();
            }
        }

        return $ordered;
    }

    /**
     * Normalise a kept set into a ReplayedHistory.
     *
     * Both paths above can leave the list in a shape the API rejects, so the
     * guards live here rather than being duplicated.
     *
     * @param  list<Message>  $kept
     * @param  Collection<int, Message>  $all  Everything in
     *                                         the thread, so
     *                                         `trimmed` reflects
     *                                         the gap cut as well
     *                                         as the budget trim.
     */
    private function result(array $kept, Collection $all): ReplayedHistory
    {
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
            $lastQuestion = $all->last(fn (Message $m) => $m->role === 'user');

            $kept = $lastQuestion === null ? [] : [$lastQuestion];
        }

        return new ReplayedHistory(
            $this->shape($kept),
            count($kept) < $all->count(),
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
