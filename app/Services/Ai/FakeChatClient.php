<?php

namespace App\Services\Ai;

use Generator;

/**
 * Zero-network stand-in for the real client.
 *
 * Emits a canned reply word by word with a small delay so the SSE plumbing and
 * the Vue typing indicator behave the way they will against the real API. Used
 * in local development and pinned on in phpunit.xml.
 */
class FakeChatClient implements ChatClient
{
    /**
     * Microseconds between chunks. Roughly the cadence of a real Haiku stream;
     * enough to expose buffering bugs, short enough not to slow tests down.
     */
    public int $delayMicroseconds = 25_000;

    public function __construct(public readonly string $model = 'fake-model') {}

    public function stream(string $system, array $messages): Generator
    {
        $reply = $this->cannedReply($messages);

        // Split on whitespace but keep it, so reassembling the chunks yields
        // the original string exactly — same invariant the real stream has.
        $chunks = preg_split('/(\s+)/u', $reply, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chunks as $chunk) {
            if ($this->delayMicroseconds > 0) {
                usleep($this->delayMicroseconds);
            }

            yield $chunk;
        }

        return new ChatResult(
            model: $this->model,
            // Rough but non-zero, so cost-accounting code has something to add up.
            inputTokens: (int) ceil(mb_strlen($system.json_encode($messages)) / 4),
            outputTokens: (int) ceil(mb_strlen($reply) / 4),
            stopReason: 'end_turn',
        );
    }

    private function cannedReply(array $messages): string
    {
        $last = end($messages);
        $question = is_array($last) ? trim((string) ($last['content'] ?? '')) : '';

        if ($question === '') {
            return 'I did not catch a question — could you ask that again?';
        }

        return sprintf(
            'This is a fake tutor reply to "%s". No request was made to the Anthropic API, '.
            'so this cost nothing. Set ANTHROPIC_DRIVER=real once the Console smoke test passes.',
            mb_strimwidth($question, 0, 80, '…'),
        );
    }
}
