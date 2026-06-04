<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(): Response
    {
        $sitemap = route('sitemap');

        $body = <<<TXT
User-agent: *
Disallow:

Sitemap: {$sitemap}
TXT;

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
