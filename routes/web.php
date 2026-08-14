<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'dashboard.index')->name('dashboard');
    Route::livewire('analytics', 'analytics.index')->name('analytics');

    Route::livewire('categories', 'categories.index')->name('categories.index');
    Route::livewire('goals', 'goals.index')->name('goals.index');
    Route::livewire('today', 'today.index')->name('today');
    Route::livewire('tasks/{task}', 'tasks.show')->name('tasks.show');

    Route::livewire('review', 'review.daily')->name('review.daily');
    Route::livewire('review/{dateOffset}', 'review.daily')->name('review.daily.date');
    Route::livewire('reports/weekly', 'reports.weekly')->name('reports.weekly');
    Route::livewire('reports/weekly/{weekOffset}', 'reports.weekly')->name('reports.weekly.offset');
    Route::livewire('reports/monthly', 'reports.monthly')->name('reports.monthly');
    Route::livewire('reports/monthly/{monthOffset}', 'reports.monthly')->name('reports.monthly.offset');
});

Route::get('/offline', function () {
    $content = file_get_contents(public_path('offline.html')) ?: '';

    return response($content, 200, ['Content-Type' => 'text/html']);
})->name('offline');

Route::get('/manifest.webmanifest', function () {
    $content = file_get_contents(public_path('manifest.webmanifest')) ?: '{}';

    return response()->json(json_decode($content, true) ?? [])
        ->header('Content-Type', 'application/manifest+json');
})->name('manifest');

Route::get('/sw.js', function () {
    return response()->file(public_path('sw.js'), ['Content-Type' => 'application/javascript']);
})->name('sw');

Route::get('/icon-192.png', function () {
    return response()->file(public_path('icon-192.png'), ['Content-Type' => 'image/png']);
});

Route::get('/icon-512.png', function () {
    return response()->file(public_path('icon-512.png'), ['Content-Type' => 'image/png']);
});

require __DIR__.'/settings.php';
