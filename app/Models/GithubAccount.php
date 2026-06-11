<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GithubAccount extends Model
{
    protected $fillable = [
        'user_id',
        'github_id',
        'username',
        'avatar_url',
        'access_token',
        'followers',
        'following',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}