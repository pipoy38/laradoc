<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\UserController;
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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
    Route::resource('articles', ArticleController::class)->except('show');
    Route::get('articles/{article}/revisions/{revision}', [RevisionController::class, 'show'])->name('articles.revisions.show');
    Route::post('articles/{article}/revisions/{revision}/restore', [RevisionController::class, 'restore'])->name('articles.revisions.restore');
    Route::resource('tags', TagController::class)->except('show');
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware('role:admin')->group(function () {
        Route::get('menu', [MenuItemController::class, 'index'])->name('menu.index');
        Route::post('menu', [MenuItemController::class, 'store'])->name('menu.store');
        Route::put('menu/{menu}', [MenuItemController::class, 'update'])->name('menu.update');
        Route::delete('menu/{menu}', [MenuItemController::class, 'destroy'])->name('menu.destroy');
        Route::post('menu/{menu}/move', [MenuItemController::class, 'move'])->name('menu.move');
        Route::resource('users', UserController::class)->except('show');
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test_mail');
        Route::get('transfer', [TransferController::class, 'index'])->name('transfer.index');
        Route::post('transfer/export', [TransferController::class, 'export'])->name('transfer.export');
        Route::post('transfer/import', [TransferController::class, 'import'])->name('transfer.import');
    });
});
