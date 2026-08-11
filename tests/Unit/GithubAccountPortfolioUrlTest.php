<?php

namespace Tests\Unit;

use App\Models\GithubAccount;
use Tests\TestCase;

class GithubAccountPortfolioUrlTest extends TestCase
{
    private function account(?string $blog): GithubAccount
    {
        return new GithubAccount(['blog' => $blog]);
    }

    public function test_url_without_scheme_is_prefixed_with_https(): void
    {
        $this->assertSame(
            'https://rin-nairith.vercel.app',
            $this->account('rin-nairith.vercel.app')->portfolio_url
        );
    }

    public function test_https_url_is_left_unchanged(): void
    {
        $this->assertSame(
            'https://rin-nairith.vercel.app',
            $this->account('https://rin-nairith.vercel.app')->portfolio_url
        );
    }

    public function test_http_url_is_upgraded_to_https(): void
    {
        $this->assertSame(
            'https://example.com',
            $this->account('http://example.com')->portfolio_url
        );
    }

    public function test_localhost_http_is_preserved(): void
    {
        $this->assertSame(
            'http://localhost:3000',
            $this->account('http://localhost:3000')->portfolio_url
        );
    }

    public function test_url_with_path_and_query_is_preserved(): void
    {
        $this->assertSame(
            'https://example.com/about?lang=en',
            $this->account('example.com/about?lang=en')->portfolio_url
        );
    }

    public function test_non_web_scheme_returns_null(): void
    {
        $this->assertNull($this->account('javascript:alert(1)')->portfolio_url);
        $this->assertNull($this->account('ftp://example.com')->portfolio_url);
    }

    public function test_empty_or_null_returns_null(): void
    {
        $this->assertNull($this->account(null)->portfolio_url);
        $this->assertNull($this->account('')->portfolio_url);
        $this->assertNull($this->account('   ')->portfolio_url);
    }

    public function test_quotes_are_stripped(): void
    {
        $this->assertSame(
            'https://example.com',
            $this->account('"https://example.com"')->portfolio_url
        );
    }
}
