<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    protected $fillable = [
        'github_account_id',
        'repo_id',
        'name',
        'description',
        'language',
        'stars',
        'forks',
        'open_issues',
        'html_url',
    ];

    public function githubAccount(): BelongsTo
    {
        return $this->belongsTo(GithubAccount::class);
    }
    
}