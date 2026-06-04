<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Seo
{
    /**
     * @return array{title: string, description: string, type: string, site_name: string, image: string, url: string, canonical: string, twitter_site: ?string}
     */
    public static function forRequest(Request $request): array
    {
        $routeName = $request->route()?->getName();
        $page = $routeName !== null
            ? (config("seo.pages.{$routeName}") ?? [])
            : [];

        if ($request->is('/') && $page === []) {
            $page = config('seo.pages.home', []);
        }

        $defaults = config('seo.default', []);
        $merged = array_merge($defaults, $page);

        $title = (string) Arr::get($merged, 'title', config('app.name', 'Eduvidra'));
        $description = (string) Arr::get($merged, 'description', '');
        $type = (string) Arr::get($merged, 'type', 'website');
        $siteName = (string) config('seo.site_name', config('app.name', 'Eduvidra'));
        $imagePath = (string) config('seo.image', '/images/og-eduvidra.jpg');
        $canonical = $request->url();

        return [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'site_name' => $siteName,
            'image' => url($imagePath),
            'url' => $canonical,
            'canonical' => $canonical,
            'twitter_site' => config('seo.twitter_site'),
        ];
    }
}
