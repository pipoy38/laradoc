<?php
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except('show');
    Route::post('categories/{category}/move', [\App\Http\Controllers\Admin\CategoryController::class, 'move'])->name('categories.move');
    Route::resource('articles', ArticleController::class)->except('show');
    Route::get('articles/{article}/revisions/{revision}', [RevisionController::class, 'show'])->name('articles.revisions.show');
    Route::post('articles/{article}/revisions/{revision}/restore', [RevisionController::class, 'restore'])->name('articles.revisions.restore');
    Route::resource('tags', TagController::class)->except('show');
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('users', fn () => 'users')->name('users.index')->middleware('role:admin'); // replaced in Task 8
});
