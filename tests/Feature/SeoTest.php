<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_home_is_public_and_indexable(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('AI GitHub Portfolio Analyzer', false);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_robots_txt_is_served(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Allow: /');
        $response->assertSee('Sitemap:');
        $response->assertSee('/sitemap.xml');
    }

    public function test_sitemap_xml_lists_only_public_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('<loc>', $content);
        $this->assertStringNotContainsString('/dashboard', $content);
        $this->assertStringNotContainsString('/profile', $content);
        $this->assertStringNotContainsString('/repositories', $content);
        $this->assertStringNotContainsString('/settings', $content);
        $this->assertStringNotContainsString('/auth/', $content);
    }

    public function test_authenticated_app_layout_has_noindex(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
