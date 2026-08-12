<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Canonical Base URL
    |--------------------------------------------------------------------------
    |
    | Used for canonical links, Open Graph URLs, sitemap entries, and
    | robots.txt. Defaults to APP_URL; override with SEO_PUBLIC_URL in .env
    | when production domain differs from local APP_URL during development.
    |
    */

    'public_url' => (static function (): string {
        $url = rtrim((string) env('SEO_PUBLIC_URL', env('APP_URL', 'http://localhost')), '/');

        // Normalize malformed scheme URLs (e.g. https:/example.com → https://example.com).
        if (preg_match('#^(https?):/([^/])#', $url)) {
            $url = preg_replace('#^(https?):/([^/])#', '$1://$2', $url);
        }

        return $url;
    })(),

    /*
    |--------------------------------------------------------------------------
    | Sitemap — Public URLs Only
    |--------------------------------------------------------------------------
    |
    | Paths listed here are emitted as absolute HTTPS URLs in sitemap.xml.
    | Do not include authenticated, OAuth, API, or debug routes.
    |
    */

    'sitemap_paths' => [
        '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | robots.txt — Disallow Private Paths
    |--------------------------------------------------------------------------
    |
    | Crawl hints for authenticated app areas. Not a substitute for auth.
    |
    */

    'robots_disallow' => [
        '/dashboard',
        '/repositories',
        '/analysis',
        '/insights',
        '/profile',
        '/settings',
        '/logout',
        '/auth/',
        '/health/ai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Preview Image
    |--------------------------------------------------------------------------
    |
    | Relative path under public/ used for og:image and twitter:image.
    |
    */

    'og_image' => 'images/og-image.png',

];
