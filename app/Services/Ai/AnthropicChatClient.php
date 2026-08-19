<?php

namespace App\Services\Ai;

use Anthropic\Client;
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
    ) {}

    public function stream(string $system, array $messages): Generator
    {
        $stream = $this->client->messages->createStream(
            maxTokens: $this->maxTokens,
            messages: $messages,
            model: $this->model,
            system: $system,
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

    public function countTokens(string $system, array $messages): int
    {
        return $this->client->messages->countTokens(
            messages: $messages,
            model: $this->model,
            system: $system,
        )->inputTokens;
    }
}
