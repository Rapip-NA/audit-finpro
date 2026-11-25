<?php

use App\Http\Controllers\PreviewController;
use App\Livewire\Login;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Workspace::class)->name('home');
Route::get('/index.html', fn () => redirect()->route('home'));
Route::livewire('/folder/{folder}', Workspace::class)->name('folders.show');
Route::livewire('/file/{file}', Workspace::class)->name('files.show');
Route::livewire('/login', Login::class)->name('login');
Route::get('/file/{file}/preview', PreviewController::class)->name('files.preview');
