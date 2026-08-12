<?php

namespace Tests\Unit;

use App\Support\Seo;
use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_public_url_normalizes_malformed_https_scheme(): void
    {
        config(['seo.public_url' => 'https:/gitradar.duckdns.org']);

        $this->assertSame('https://gitradar.duckdns.org', Seo::publicUrl());
    }

    public function test_public_url_leaves_valid_urls_unchanged(): void
    {
        config(['seo.public_url' => 'https://gitradar.duckdns.org']);

        $this->assertSame('https://gitradar.duckdns.org', Seo::publicUrl());
    }
}
