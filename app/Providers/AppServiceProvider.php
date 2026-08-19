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
use Illuminate\Support\Facades\Auth;
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

        // 30 days (in minutes) for "remember me" cookie duration.
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->setRememberDuration(60 * 24 * 30);
    }
}
