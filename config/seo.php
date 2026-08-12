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

    'public_url' => rtrim(env('SEO_PUBLIC_URL', env('APP_URL', 'http://localhost')), '/'),

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
    | Social Preview Image
    |--------------------------------------------------------------------------
    |
    | Relative path under public/ used for og:image and twitter:image.
    |
    */

    'og_image' => 'images/og-image.png',

];
