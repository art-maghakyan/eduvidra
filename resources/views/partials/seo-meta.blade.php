@php
    $seo = $seo ?? \App\Support\Seo::forRequest(request());
@endphp

<meta name="description" content="{{ $seo['description'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['url'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:alt" content="{{ $seo['title'] }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
@if (! empty($seo['twitter_site']))
<meta name="twitter:site" content="{{ $seo['twitter_site'] }}">
@endif

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $seo['site_name'],
    'url' => config('app.url'),
    'description' => $seo['description'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
