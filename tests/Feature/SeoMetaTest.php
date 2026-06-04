<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_homepage_includes_open_graph_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('Eduvidra', false);
        $response->assertSee('/images/og-eduvidra.jpg', false);
    }

    public function test_sitemap_is_available(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_robots_txt_includes_sitemap(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Sitemap:', false)
            ->assertSee(route('sitemap'), false);
    }
}
