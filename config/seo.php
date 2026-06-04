<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default page metadata (Open Graph, Twitter, HTML)
    |--------------------------------------------------------------------------
    |
    | Used for social previews and search snippets. Override per route in
    | the "pages" array below. Image path is relative to the site root.
    |
    */

    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'Eduvidra')),

    'image' => env('SEO_OG_IMAGE', '/images/og-eduvidra.jpg'),

    'twitter_site' => env('SEO_TWITTER_SITE'),

    'default' => [
        'title' => 'Eduvidra — platform for students',
        'description' => 'Sign in to access your cabinet. New here? Create an account in a minute.',
        'type' => 'website',
    ],

    'pages' => [
        'home' => [
            'title' => 'Eduvidra — platform for students',
            'description' => 'Sign in to access your cabinet. New here? Create an account in a minute.',
            'type' => 'website',
        ],
    ],

];
