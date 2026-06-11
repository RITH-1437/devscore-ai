<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GitHubController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/github', [GitHubController::class, 'redirect'])
    ->name('github.login');

Route::get('/auth/github/callback', [GitHubController::class, 'callback']);

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');