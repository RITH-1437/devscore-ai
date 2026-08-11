<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;

class RepositoryPolicy
{
    /**
     * Determine if the user can view the repository.
     * Users can only view their own repositories.
     */
    public function view(User $user, Repository $repository): bool
    {
        return $repository->githubAccount?->user_id === $user->id;
    }

    /**
     * Determine if the user can run analysis on the repository.
     */
    public function analyze(User $user, Repository $repository): bool
    {
        return $repository->githubAccount?->user_id === $user->id;
    }

    /**
     * Determine if the user can update repository preferences (pin, feature).
     */
    public function update(User $user, Repository $repository): bool
    {
        return $repository->githubAccount?->user_id === $user->id;
    }
}
