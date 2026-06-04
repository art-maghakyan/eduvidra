<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $lastmod = now()->toAtomString();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{$base}/</loc>
    <lastmod>{$lastmod}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
