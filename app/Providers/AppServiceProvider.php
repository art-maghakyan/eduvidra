<?php

namespace App\Providers;

use Anthropic\Client as AnthropicClient;
use Anthropic\RequestOptions;
use App\Services\Ai\AnthropicChatClient;
use App\Services\Ai\ChatClient;
use App\Services\Ai\FakeChatClient;
use App\Services\Ai\IncrementalStreamTransporter;
use Aws\Sns\MessageValidator;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Verifies SNS signatures on the SES bounce/complaint webhook.
        // Bound here so tests can swap in a validator with a stubbed
        // certificate client instead of reaching out to AWS.
        $this->app->singleton(MessageValidator::class, fn () => new MessageValidator);

        // The AI tutor's only route to Anthropic. `fake` makes zero network
        // calls and costs nothing, which covers most of the build; phpunit.xml
        // pins it on so no test can spend money.
        $this->app->singleton(ChatClient::class, function () {
            if (config('anthropic.driver') !== 'real') {
                return new FakeChatClient;
            }

            return new AnthropicChatClient(
                new AnthropicClient(
                    apiKey: config('anthropic.api_key'),
                    // Without this the SDK's 8 KB reads buffer the whole reply
                    // before yielding a single token — see the class docblock.
                    requestOptions: RequestOptions::with(
                        streamingTransporter: new IncrementalStreamTransporter(
                            Psr18ClientDiscovery::find(),
                        ),
                    ),
                ),
                config('anthropic.model'),
                config('anthropic.max_tokens'),
                config('anthropic.cache_ttl'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->rateLimiters();

        // 30 days (in minutes) for "remember me" cookie duration.
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->setRememberDuration(60 * 24 * 30);
    }

    /**
     * Named rate limiters.
     *
     * This is the first `RateLimiter::for` in the application — the auth
     * controllers throttle imperatively with RateLimiter::hit instead.
     */
    private function rateLimiters(): void
    {
        // Abuse control for the AI tutor, distinct from the daily quota: the
        // quota is a business cap in questions per day, this bounds requests
        // per minute. It matters most for requests the quota *rejects*, which
        // cost nothing in tokens but still cost a database query and an
        // FPM worker each, and so are free to spam without it.
        //
        // Keyed by user id, never IP — students behind one school or household
        // NAT would otherwise share a single allowance.
        RateLimiter::for('ai-chat', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            $tooMany = fn () => response()->json([
                'message' => 'You are sending questions faster than the tutor can answer them. Please wait a moment and try again.',
            ], 429);

            return [
                Limit::perMinute(5)->by("ai-chat-burst:{$key}")->response($tooMany),
                Limit::perHour(30)->by("ai-chat-hour:{$key}")->response($tooMany),
            ];
        });
    }
}
