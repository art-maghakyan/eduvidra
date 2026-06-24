<?php

namespace App\Http\Controllers;

use App\Enums\SuppressionType;
use App\Models\EmailSuppression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SesWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload) || ! isset($payload['Type'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        return match ($payload['Type']) {
            'SubscriptionConfirmation' => $this->handleSubscriptionConfirmation($payload),
            'Notification' => $this->handleNotification($payload),
            default => response()->json(['error' => 'Unknown type'], 400),
        };
    }

    private function handleSubscriptionConfirmation(array $payload): JsonResponse
    {
        $url = $payload['SubscribeURL'] ?? null;

        if (! $url || ! str_starts_with($url, 'https://sns.')) {
            return response()->json(['error' => 'Invalid SubscribeURL'], 400);
        }

        Http::get($url);

        Log::info('SNS subscription confirmed', ['url' => $url]);

        return response()->json(['status' => 'confirmed']);
    }

    private function handleNotification(array $payload): JsonResponse
    {
        $message = json_decode($payload['Message'] ?? '', true);

        if (! is_array($message)) {
            return response()->json(['error' => 'Invalid Message JSON'], 400);
        }

        match ($message['notificationType'] ?? null) {
            'Bounce' => $this->processBounce($message),
            'Complaint' => $this->processComplaint($message),
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
