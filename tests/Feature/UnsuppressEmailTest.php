<?php

namespace Tests\Feature;

use App\Enums\SuppressionType;
use App\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsuppressEmailTest extends TestCase
{
    use RefreshDatabase;

    private function suppress(string $email): EmailSuppression
    {
        return EmailSuppression::create([
            'email' => $email,
            'type' => SuppressionType::BOUNCE,
            'subtype' => 'General',
        ]);
    }

    public function test_it_removes_a_suppressed_address(): void
    {
        $this->suppress('bad@example.com');

        $this->artisan('mail:unsuppress', ['email' => 'bad@example.com'])
            ->expectsOutput('Removed bad@example.com from the suppression list.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('email_suppressions', ['email' => 'bad@example.com']);
    }

    public function test_it_reports_when_the_address_is_not_suppressed(): void
    {
        $this->suppress('someone@example.com');

        $this->artisan('mail:unsuppress', ['email' => 'nobody@example.com'])
            ->expectsOutput('nobody@example.com is not suppressed.')
            ->assertExitCode(1);

        // The unrelated row must survive.
        $this->assertDatabaseCount('email_suppressions', 1);
    }

    public function test_it_matches_regardless_of_casing(): void
    {
        // SES stores the casing from the original message; the operator types
        // the address by hand and rarely reproduces it exactly.
        $this->suppress('John.Doe@Example.com');

        $this->artisan('mail:unsuppress', ['email' => 'john.doe@example.com'])
            ->expectsOutput('Removed John.Doe@Example.com from the suppression list.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('email_suppressions', 0);
    }

    public function test_is_suppressed_matches_regardless_of_casing(): void
    {
        $this->suppress('John.Doe@Example.com');

        $this->assertTrue(EmailSuppression::isSuppressed('john.doe@example.com'));
        $this->assertTrue(EmailSuppression::isSuppressed('JOHN.DOE@EXAMPLE.COM'));
        $this->assertFalse(EmailSuppression::isSuppressed('someone.else@example.com'));
    }
}
