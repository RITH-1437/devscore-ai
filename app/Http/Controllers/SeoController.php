<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = Seo::publicUrl().'/sitemap.xml';

        $lines = ['User-agent: *'];

        foreach (config('seo.robots_disallow', []) as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = "Sitemap: {$sitemap}";

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $base = Seo::publicUrl();

        $entries = collect(config('seo.sitemap_paths', []))
            ->map(function (string $path): array {
                $path = '/'.ltrim($path, '/');

                return [
                    'path' => $path,
                    'changefreq' => 'weekly',
                    'priority' => '1.0',
                    'lastmod_file' => $path === '/'
                        ? resource_path('views/landing.blade.php')
                        : null,
                ];
            })
            ->unique('path')
            ->values();

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $url = $entry['path'] === '/'
                ? $base.'/'
                : $base.$entry['path'];

            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

            $lastmodFile = $entry['lastmod_file'] ?? null;
            if (is_string($lastmodFile) && is_file($lastmodFile)) {
                $lines[] = '    <lastmod>'.gmdate('Y-m-d', filemtime($lastmodFile)).'</lastmod>';
            }

            $lines[] = '    <changefreq>'.$entry['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$entry['priority'].'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
