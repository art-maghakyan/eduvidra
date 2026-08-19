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

];
