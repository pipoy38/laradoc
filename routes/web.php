<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('front.home'))->name('home');
