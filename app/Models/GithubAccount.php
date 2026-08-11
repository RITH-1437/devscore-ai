<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GithubAccount extends Model
{
    protected $fillable = [
        'user_id',
        'github_id',
        'username',
        'name',
        'avatar_url',
        'bio',
        'company',
        'location',
        'blog',
        'email',
        'twitter_username',
        'hireable',
        'access_token',
        'followers',
        'following',
        'public_repos',
        'public_gists',
        'github_created_at',
        'github_updated_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'followers'         => 'integer',
        'following'         => 'integer',
        'public_repos'      => 'integer',
        'public_gists'      => 'integer',
        'github_created_at' => 'datetime',
        'github_updated_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /**
     * The user's portfolio (GitHub "blog") URL, normalized to a safe external
     * HTTPS link. Returns null when no portfolio is configured or when the
     * value is not a valid web URL — never a Laravel/localhost route.
     */
    public function getPortfolioUrlAttribute(): ?string
    {
        $url = trim((string) ($this->blog ?? ''));

        if ($url === '') {
            return null;
        }

        // Strip surrounding quotes that GitHub sometimes stores.
        $url = trim($url, "\"' \t\n\r\0\x0B");

        // Only allow http(s) scheme once normalized.
        if (! str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $parsed = parse_url($url);

        if ($parsed === false || empty($parsed['host'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        // Prefer HTTPS. Plain http is preserved only for loopback/local
        // development hosts, otherwise it is upgraded to HTTPS.
        if ($scheme === 'http') {
            $host = strtolower((string) $parsed['host']);
            $isLoopback = $host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]';

            if (! $isLoopback) {
                $parsed['scheme'] = 'https';
            }
        }

        return $this->buildUrl($parsed);
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function buildUrl(array $parts): string
    {
        $scheme   = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host     = $parts['host'] ?? '';
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path     = $parts['path'] ?? '';
        $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
    }
}
