<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Per-job retry policy — overrides the worker's global --tries flag.
    public int $tries = 3;

    // Backoff between the 2 retry gaps (attempt 1→2: 10s, attempt 2→3: 30s).
    public array $backoff = [10, 30];

    // Discard the job silently if the user no longer exists when the worker picks it up.
    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').'!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
        );
    }
}
