<?php

namespace App\Http\Controllers;

use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $repositories = Repository::latest()->get();

        return view('dashboard', [
            'repositories' => $repositories,
        ]);
    }
}