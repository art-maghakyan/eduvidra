<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawMessageDeltaEvent;
use Anthropic\Messages\RawMessageStartEvent;
use Anthropic\Messages\TextDelta;
use Generator;

/**
 * The real client. The only class in the codebase that talks to the SDK.
 *
 * Note the SDK takes camelCase named arguments (maxTokens:) even though the
 * wire format is snake_case.
 */
class AnthropicChatClient implements ChatClient
{
    public function __construct(
        private readonly Client $client,
        private readonly string $model,
        private readonly int $maxTokens,
        /**
         * Cache TTL: null for the API default of 5 minutes, '1h' for an hour,
         * or false to send no breakpoint at all.
         */
        private readonly string|false|null $cacheTtl = null,
    ) {}

    public function stream(string $system, array $messages): Generator
    {
        $stream = $this->client->messages->createStream(
            maxTokens: $this->maxTokens,
            messages: $messages,
            model: $this->model,
            system: $system,
            cacheControl: $this->cacheControl(),
        );

        $model = $this->model;
        $inputTokens = 0;
        $outputTokens = 0;
        $cacheCreationTokens = 0;
        $cacheReadTokens = 0;
        $stopReason = null;

        foreach ($stream as $event) {
            // Unknown event types must be ignored rather than thrown on — the
            // API adds new ones over time, and `ping` arrives at any point.
            if ($event instanceof RawMessageStartEvent) {
                $usage = $event->message->usage;
                $model = $event->message->model;
                $inputTokens = $usage->inputTokens;
                $outputTokens = $usage->outputTokens;
                $cacheCreationTokens = $usage->cacheCreationInputTokens ?? 0;
                $cacheReadTokens = $usage->cacheReadInputTokens ?? 0;

                continue;
            }

            if ($event instanceof RawContentBlockDeltaEvent) {
                if ($event->delta instanceof TextDelta) {
                    yield $event->delta->text;
                }

                continue;
            }

            if ($event instanceof RawMessageDeltaEvent) {
                // message_delta carries the cumulative output count and the
                // final stop reason.
                $outputTokens = $event->usage->outputTokens;
                $stopReason = $event->delta->stopReason;
            }
        }

        return new ChatResult(
            model: $model,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cacheCreationTokens: $cacheCreationTokens,
            cacheReadTokens: $cacheReadTokens,
            stopReason: $stopReason,
        );
    }

    /**
     * One breakpoint at the end of the request, which caches everything before
     * it: the system prompt plus the entire replayed history. The next turn in
     * the same thread re-sends that identical prefix and reads it at 0.1x.
     *
     * Below 4,096 tokens on Haiku 4.5 this is **silently ignored** — no error,
     * just zeros in usage. That is expected on a young thread, not a bug.
     */
    private function cacheControl(): ?CacheControlEphemeral
    {
        if ($this->cacheTtl === false) {
            return null;
        }

        return CacheControlEphemeral::with(ttl: $this->cacheTtl);
    }

    public function countTokens(string $system, array $messages): int
    {
        return $this->client->messages->countTokens(
            messages: $messages,
            model: $this->model,
            system: $system,
        )->inputTokens;
    }
}
