<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GitHubController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/github', [GitHubController::class, 'redirect'])
    ->name('github.login');

Route::get('/auth/github/callback', [GitHubController::class, 'callback']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::get('/logout-test', function () {

    Auth::logout();

    request()->session()->invalidate();

    request()->session()->regenerateToken();

    return redirect('/');

});

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', function () {
    return redirect('/auth/github');
})->name('login');