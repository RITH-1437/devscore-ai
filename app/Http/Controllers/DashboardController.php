<?php

namespace App\Http\Controllers;

use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $repositories = auth()->user()
    ->githubAccount
    ->repositories()
    ->latest()
    ->get();

        $totalRepos = $repositories->count();

        $totalStars = $repositories->sum('stars');

        $topLanguages = $repositories
            ->groupBy('language')
            ->map(fn ($repos) => $repos->count())
            ->sortDesc();

        return view('dashboard.index', [
            'repositories' => $repositories,
            'totalRepos' => $totalRepos,
            'totalStars' => $totalStars,
            'topLanguages' => $topLanguages,
        ]);
    }
}