<?php

namespace Tests\Feature;

use App\Enums\SuppressionType;
use App\Models\EmailSuppression;
use Aws\Sns\MessageValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use OpenSSLAsymmetricKey;
use Tests\TestCase;

class SesWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOPIC_ARN = 'arn:aws:sns:eu-central-1:123456789012:eduvidra-ses-events';

    private const CERT_URL = 'https://sns.eu-central-1.amazonaws.com/SimpleNotificationService-test.pem';

    private OpenSSLAsymmetricKey $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ses.webhook.topic_arn' => self::TOPIC_ARN]);

        // Sign test payloads with a throwaway keypair and hand the matching
        // public key to the validator, so the real signature-verification path
        // runs without reaching out to AWS for a certificate.
        $this->privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $publicKey = openssl_pkey_get_details($this->privateKey)['key'];

        $this->app->instance(MessageValidator::class, new MessageValidator(
            certClient: fn () => $publicKey,
        ));
    }

    /**
     * Builds a complete SNS envelope and signs it exactly as SNS would.
     */
    private function signedPayload(array $overrides = []): array
    {
        $payload = array_merge([
            'Type' => 'Notification',
            'MessageId' => (string) Str::uuid(),
            'TopicArn' => self::TOPIC_ARN,
            'Message' => '{}',
            'Timestamp' => now()->toIso8601ZuluString(),
            'SignatureVersion' => '2',
            'SigningCertURL' => self::CERT_URL,
        ], $overrides);

        $payload['Signature'] = $this->sign($payload);

        return $payload;
    }

    private function sign(array $payload): string
    {
        $signableKeys = ['Message', 'MessageId', 'Subject', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $stringToSign = '';
        foreach ($signableKeys as $key) {
            if (isset($payload[$key])) {
                $stringToSign .= "{$key}\n{$payload[$key]}\n";
            }
        }

        openssl_sign($stringToSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    private function bouncePayload(string $bounceType, string $bounceSubType, array $emails): array
    {
        return $this->signedPayload(['Message' => json_encode([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => $bounceType,
                'bounceSubType' => $bounceSubType,
                'bouncedRecipients' => array_map(fn ($e) => ['emailAddress' => $e], $emails),
            ],
        ])]);
    }

    private function complaintPayload(array $emails): array
    {
        return $this->signedPayload(['Message' => json_encode([
            'notificationType' => 'Complaint',
            'complaint' => [
                'complainedRecipients' => array_map(fn ($e) => ['emailAddress' => $e], $emails),
            ],
        ])]);
    }

    // ---------------------------------------------------------------- signature

    public function test_message_with_tampered_body_is_rejected(): void
    {
        $payload = $this->bouncePayload('Permanent', 'General', ['victim@example.com']);

        // Valid signature, but the body is swapped after signing.
        $payload['Message'] = json_encode([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bounceSubType' => 'General',
                'bouncedRecipients' => [['emailAddress' => 'attacker-target@example.com']],
            ],
        ]);

        $this->postJson('/webhooks/ses', $payload)->assertStatus(403);

        $this->assertDatabaseCount('email_suppressions', 0);
    }

    public function test_message_with_forged_signature_is_rejected(): void
    {
        $payload = $this->bouncePayload('Permanent', 'General', ['victim@example.com']);
        $payload['Signature'] = base64_encode('not-a-real-signature');

        $this->postJson('/webhooks/ses', $payload)->assertStatus(403);

        $this->assertDatabaseMissing('email_suppressions', ['email' => 'victim@example.com']);
    }

    public function test_unsigned_payload_is_rejected(): void
    {
        $payload = $this->bouncePayload('Permanent', 'General', ['victim@example.com']);
        unset($payload['Signature'], $payload['SigningCertURL'], $payload['SignatureVersion']);

        $this->postJson('/webhooks/ses', $payload)->assertStatus(400);

        $this->assertDatabaseCount('email_suppressions', 0);
    }

    // -------------------------------------------------------------------- topic

    public function test_message_from_unknown_topic_is_rejected(): void
    {
        $payload = $this->bouncePayload('Permanent', 'General', ['victim@example.com']);
        $payload['TopicArn'] = 'arn:aws:sns:eu-central-1:999999999999:someone-elses-topic';
        $payload['Signature'] = $this->sign($payload);

        $this->postJson('/webhooks/ses', $payload)->assertStatus(403);

        $this->assertDatabaseCount('email_suppressions', 0);
    }

    public function test_all_messages_are_rejected_when_no_topic_is_configured(): void
    {
        config(['services.ses.webhook.topic_arn' => null]);

        $this->postJson('/webhooks/ses', $this->bouncePayload('Permanent', 'General', ['bad@example.com']))
            ->assertStatus(403);

        $this->assertDatabaseCount('email_suppressions', 0);
    }

    // ------------------------------------------------------------ subscriptions

    public function test_subscription_confirmation_is_handled(): void
    {
        Http::fake(['https://sns.*' => Http::response('', 200)]);

        $response = $this->postJson('/webhooks/ses', $this->signedPayload([
            'Type' => 'SubscriptionConfirmation',
            'SubscribeURL' => 'https://sns.eu-central-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc',
            'Token' => 'abc',
        ]));

        $response->assertOk()->assertJson(['status' => 'confirmed']);
        Http::assertSentCount(1);
    }

    public function test_subscription_confirmation_rejects_non_sns_subscribe_url(): void
    {
        Http::fake();

        $response = $this->postJson('/webhooks/ses', $this->signedPayload([
            'Type' => 'SubscriptionConfirmation',
            'SubscribeURL' => 'https://sns.evil.example.com/steal',
            'Token' => 'abc',
        ]));

        $response->assertStatus(400);
        Http::assertNothingSent();
    }

    // ------------------------------------------------------------- notifications

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

    // ------------------------------------------------------------------ payloads

    public function test_invalid_payload_returns_400(): void
    {
        $this->postJson('/webhooks/ses', [])->assertStatus(400);
    }

    public function test_unknown_type_returns_400(): void
    {
        $this->postJson('/webhooks/ses', $this->signedPayload(['Type' => 'UnsubscribeConfirmation', 'Token' => 'abc', 'SubscribeURL' => 'https://sns.eu-central-1.amazonaws.com/?Action=Unsubscribe']))
            ->assertStatus(400);
    }

    public function test_non_json_notification_body_returns_400(): void
    {
        $this->postJson('/webhooks/ses', $this->signedPayload(['Message' => 'not-json']))
            ->assertStatus(400);
    }
}
