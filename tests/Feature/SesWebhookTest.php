<?php

namespace Tests\Feature;

use App\Enums\SuppressionType;
use App\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SesWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function bouncePayload(string $bounceType, string $bounceSubType, array $emails): array
    {
        return [
            'Type' => 'Notification',
            'Message' => json_encode([
                'notificationType' => 'Bounce',
                'bounce' => [
                    'bounceType' => $bounceType,
                    'bounceSubType' => $bounceSubType,
                    'bouncedRecipients' => array_map(fn ($e) => ['emailAddress' => $e], $emails),
                ],
            ]),
        ];
    }

    private function complaintPayload(array $emails): array
    {
        return [
            'Type' => 'Notification',
            'Message' => json_encode([
                'notificationType' => 'Complaint',
                'complaint' => [
                    'complainedRecipients' => array_map(fn ($e) => ['emailAddress' => $e], $emails),
                ],
            ]),
        ];
    }

    public function test_subscription_confirmation_is_handled(): void
    {
        Http::fake(['https://sns.*' => Http::response('', 200)]);

        $response = $this->postJson('/webhooks/ses', [
            'Type' => 'SubscriptionConfirmation',
            'SubscribeURL' => 'https://sns.eu-central-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc',
        ]);

        $response->assertOk()->assertJson(['status' => 'confirmed']);
        Http::assertSentCount(1);
    }

    public function test_subscription_confirmation_rejects_invalid_url(): void
    {
        $response = $this->postJson('/webhooks/ses', [
            'Type' => 'SubscriptionConfirmation',
            'SubscribeURL' => 'http://evil.example.com/steal',
        ]);

        $response->assertStatus(400);
    }

    public function test_permanent_bounce_suppresses_email(): void
    {
        $response = $this->postJson('/webhooks/ses', $this->bouncePayload('Permanent', 'General', ['bad@example.com']));

        $response->assertOk()->assertJson(['status' => 'ok']);

        $suppression = EmailSuppression::where('email', 'bad@example.com')->first();
        $this->assertNotNull($suppression);
        $this->assertSame(SuppressionType::BOUNCE, $suppression->type);
        $this->assertSame('General', $suppression->subtype);
    }

    public function test_transient_bounce_is_not_suppressed(): void
    {
        $this->postJson('/webhooks/ses', $this->bouncePayload('Transient', 'MailboxFull', ['full@example.com']))
            ->assertOk();

        $this->assertDatabaseMissing('email_suppressions', ['email' => 'full@example.com']);
    }

    public function test_complaint_suppresses_email(): void
    {
        $response = $this->postJson('/webhooks/ses', $this->complaintPayload(['angry@example.com']));

        $response->assertOk();
        $this->assertDatabaseHas('email_suppressions', [
            'email' => 'angry@example.com',
            'type' => 'complaint',
            'subtype' => null,
        ]);
    }

    public function test_duplicate_suppression_is_idempotent(): void
    {
        EmailSuppression::create([
            'email' => 'dup@example.com',
            'type' => SuppressionType::BOUNCE,
            'subtype' => 'General',
        ]);

        $this->postJson('/webhooks/ses', $this->bouncePayload('Permanent', 'General', ['dup@example.com']))
            ->assertOk();

        $this->assertSame(1, EmailSuppression::where('email', 'dup@example.com')->count());
    }

    public function test_invalid_payload_returns_400(): void
    {
        $this->postJson('/webhooks/ses', [])->assertStatus(400);
    }

    public function test_unknown_type_returns_400(): void
    {
        $this->postJson('/webhooks/ses', ['Type' => 'UnsubscribeConfirmation'])->assertStatus(400);
    }
}
