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

];
