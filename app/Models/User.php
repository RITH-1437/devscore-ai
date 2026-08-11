<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function githubAccount(): HasOne
    {
        return $this->hasOne(GithubAccount::class);
    }

    public function repositories(): HasManyThrough
    {
        return $this->hasManyThrough(
            Repository::class,
            GithubAccount::class,
        );
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    /**
     * Get repositories scoped to this user via their GitHub account.
     */
    public function ownedRepositories(): HasManyThrough
    {
        return $this->hasManyThrough(
            Repository::class,
            GithubAccount::class,
        );
    }
}
