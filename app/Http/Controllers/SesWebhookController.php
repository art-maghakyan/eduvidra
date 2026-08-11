<?php

namespace App\Http\Controllers;

use App\Enums\SuppressionType;
use App\Models\EmailSuppression;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Receives SES bounce/complaint notifications delivered over SNS.
 *
 * The endpoint is necessarily public, so every delivery is authenticated twice
 * before it can touch the suppression list:
 *
 *  1. The SNS signature is verified against the AWS-hosted signing certificate,
 *     which proves the payload was produced by SNS and not tampered with.
 *  2. The topic ARN is checked against an allow-list, because a valid signature
 *     only proves *some* AWS account sent it — without this check any AWS
 *     customer could point their own topic at us and suppress arbitrary
 *     addresses.
 */
class SesWebhookController extends Controller
{
    /**
     * Matches the regional SNS endpoints (including GovCloud and China).
     * MessageValidator applies this to the signing certificate URL; we reapply
     * it to the SubscribeURL before following it, so that a confirmation
     * request can never be used to make this server fetch an arbitrary URL.
     */
    private const SNS_HOST_PATTERN = '/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/';

    public function __construct(private readonly MessageValidator $validator) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $message = Message::fromJsonString($request->getContent());
        } catch (RuntimeException|InvalidArgumentException) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            $this->validator->validate($message);
        } catch (InvalidSnsMessageException $e) {
            Log::warning('Rejected SNS delivery: invalid signature', [
                'message_id' => $message['MessageId'],
                'topic_arn' => $message['TopicArn'],
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        if (! $this->isAllowedTopic($message['TopicArn'])) {
            Log::warning('Rejected SNS delivery: unrecognised topic', [
                'message_id' => $message['MessageId'],
                'topic_arn' => $message['TopicArn'],
            ]);

            return response()->json(['error' => 'Unrecognised topic'], 403);
        }

        return match ($message['Type']) {
            'SubscriptionConfirmation' => $this->handleSubscriptionConfirmation($message),
            'Notification' => $this->handleNotification($message),
            default => response()->json(['error' => 'Unknown type'], 400),
        };
    }

    /**
     * A verified signature only proves SNS sent the message — not that *our*
     * topic sent it. Fail closed when no allow-list is configured.
     */
    private function isAllowedTopic(?string $topicArn): bool
    {
        $allowed = array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) config('services.ses.webhook.topic_arn')),
        )));

        if ($allowed === []) {
            Log::error('SES_WEBHOOK_TOPIC_ARN is not configured; rejecting all SNS deliveries.');

            return false;
        }

        return in_array($topicArn, $allowed, true);
    }

    private function handleSubscriptionConfirmation(Message $message): JsonResponse
    {
        $url = (string) ($message['SubscribeURL'] ?? $message['SubscribeUrl']);
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https'
            || ! preg_match(self::SNS_HOST_PATTERN, $parts['host'] ?? '')
        ) {
            Log::warning('Rejected SNS subscription confirmation: invalid SubscribeURL host', [
                'topic_arn' => $message['TopicArn'],
                'host' => $parts['host'] ?? null,
            ]);

            return response()->json(['error' => 'Invalid SubscribeURL'], 400);
        }

        // The URL carries a single-use confirmation token — keep it out of logs.
        Http::timeout(10)->get($url)->throw();

        Log::info('SNS subscription confirmed', ['topic_arn' => $message['TopicArn']]);

        return response()->json(['status' => 'confirmed']);
    }

    private function handleNotification(Message $message): JsonResponse
    {
        $payload = json_decode((string) $message['Message'], true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'Invalid Message JSON'], 400);
        }

        match ($payload['notificationType'] ?? null) {
            'Bounce' => $this->processBounce($payload),
            'Complaint' => $this->processComplaint($payload),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function processBounce(array $message): void
    {
        $bounceType = $message['bounce']['bounceType'] ?? null;
        $bounceSubType = $message['bounce']['bounceSubType'] ?? null;
        $recipients = $message['bounce']['bouncedRecipients'] ?? [];

        if ($bounceType !== 'Permanent') {
            return;
        }

        foreach ($recipients as $recipient) {
            $email = $recipient['emailAddress'] ?? null;
            if (! $email) {
                continue;
            }

            EmailSuppression::firstOrCreate(
                ['email' => $email],
                ['type' => SuppressionType::BOUNCE, 'subtype' => $bounceSubType],
            );

            Log::warning('Email suppressed due to hard bounce', [
                'email' => $email,
                'subtype' => $bounceSubType,
            ]);
        }
    }

    private function processComplaint(array $message): void
    {
        $recipients = $message['complaint']['complainedRecipients'] ?? [];

        foreach ($recipients as $recipient) {
            $email = $recipient['emailAddress'] ?? null;
            if (! $email) {
                continue;
            }

            EmailSuppression::firstOrCreate(
                ['email' => $email],
                ['type' => SuppressionType::COMPLAINT, 'subtype' => null],
            );

            Log::warning('Email suppressed due to complaint', ['email' => $email]);
        }
    }
}
