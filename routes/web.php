<?php

use Illuminate\Support\Facades\Route;

// ─── Portal Publik ──────────────────────────────────────────────
Route::get('/', function () {
    return view('landing');
});

// ─── Dashboard Internal SIMRS (Petugas) ─────────────────────────
Route::get('/app', function () {
    return view('welcome');
})->name('simrs.dashboard');

// ─── Login Petugas ──────────────────────────────────────────────
Route::get('/login', function () {
    return view('login');
})->name('login');
