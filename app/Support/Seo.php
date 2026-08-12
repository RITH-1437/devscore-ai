<?php

namespace App\Support;

class Seo
{
    public static function publicUrl(?string $url = null): string
    {
        $url = rtrim($url ?? (string) config('seo.public_url'), '/');

        if (preg_match('#^(https?):/([^/])#', $url)) {
            $url = preg_replace('#^(https?):/([^/])#', '$1://$2', $url);
        }

        return $url;
    }
}
