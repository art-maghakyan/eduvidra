<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | "fake" returns canned chunks and makes zero network calls — it covers most
    | of the build (SSE plumbing, Vue, schema, queues, rate limits) at $0. "real"
    | talks to api.anthropic.com and costs money on every call.
    |
    | phpunit.xml pins this to "fake" so no test can ever spend money.
    |
    */

    'driver' => env('ANTHROPIC_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Credentials and model
    |--------------------------------------------------------------------------
    |
    | This file is the ONLY place allowed to call env() for these values.
    | Production runs config:cache, after which env() returns null everywhere
    | else — all other code must read config('anthropic.*').
    |
    | The key never reaches the browser: no Inertia prop, no VITE_ prefix, no
    | client-side calls to api.anthropic.com.
    |
    */

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),

    /*
    |--------------------------------------------------------------------------
    | Response ceiling
    |--------------------------------------------------------------------------
    |
    | Hard cap on tokens generated per reply. A tutor answer does not need more
    | than this, and it bounds the cost of a runaway loop.
    |
    */

    'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Stream timeout
    |--------------------------------------------------------------------------
    |
    | Seconds a streaming reply may run before PHP kills it. Without this the
    | stream dies at PHP's built-in 30s default (no php.ini is loaded in the
    | image, so that default is what FPM actually uses — verified, not assumed).
    |
    | Keep it BELOW nginx's fastcgi_read_timeout, which is 300s in both
    | docker/nginx/default.conf and prod.conf. PHP timing out first produces a
    | logged error; nginx timing out first produces a 504 with no trace of why.
    |
    */

    'stream_timeout' => (int) env('ANTHROPIC_STREAM_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Prompt caching
    |--------------------------------------------------------------------------
    |
    | A cache breakpoint is placed at the end of each request, so the next turn
    | in the same thread re-reads the system prompt and the whole replayed
    | history at 0.1x input price instead of 1x.
    |
    | Two conditions have to hold before a single token is cached, and both are
    | easy to miss because a miss is silent — no error, just zeros in
    | cache_read_tokens:
    |
    |   1. The prefix must exceed 4,096 tokens on Haiku 4.5. Our system prompt is
    |      420, so this is reached by replayed history alone, at roughly turn 14.
    |   2. The next turn must arrive inside the TTL.
    |
    | TTL: null uses the API default of 5 minutes (writes cost 1.25x input).
    | '1h' costs 2x on write, so it pays only if a thread averages more than
    | about one extra turn per hour. Start at the default and let the
    | cache_read_tokens column decide — that is what it is there for.
    |
    | Set to false to disable caching entirely.
    |
    */

    'cache_ttl' => env('ANTHROPIC_CACHE_TTL'),

    /*
    |--------------------------------------------------------------------------
    | Daily quota
    |--------------------------------------------------------------------------
    |
    | Questions one user may ask per calendar day, across every course. This is
    | an abuse control, not a budget one — at ~$0.004/turn the cap costs about
    | $0.04/user/day, which is not what would hurt. One scripted user hammering
    | the endpoint is.
    |
    */

    'daily_message_limit' => (int) env('ANTHROPIC_DAILY_MESSAGE_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Replayed history budget
    |--------------------------------------------------------------------------
    |
    | The whole thread is replayed on every turn, so an old thread costs more
    | per question than a new one — about +282 input tokens per turn. These two
    | numbers bound that.
    |
    | Both sit ABOVE Haiku 4.5's 4,096-token cache floor on purpose. A budget
    | that held the prompt under 4,096 would make prompt caching permanently
    | inert, which costs far more than the trimming saves.
    |
    | The gap between them matters just as much as their values. Trimming drops
    | the OLDEST turns, which shifts the start of the cached prefix and
    | invalidates the entire cache. Trimming all the way down to the floor
    | instead of just under the budget means that happens roughly every 20
    | turns rather than on every single turn once the budget is reached.
    |
    */

    'history_token_budget' => (int) env('ANTHROPIC_HISTORY_TOKEN_BUDGET', 12000),

    'history_token_floor' => (int) env('ANTHROPIC_HISTORY_TOKEN_FLOOR', 6000),

];
