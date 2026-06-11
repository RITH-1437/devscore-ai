<?php

namespace App\Http\Controllers;

use App\Models\Repository;

class RepositoryController extends Controller
{
    public function show(Repository $repository)
    {
        return view('repositories.show', [
            'repository' => $repository,
        ]);
    }
}