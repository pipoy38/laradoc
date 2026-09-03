<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

it('renders the front layout with a theme toggle and search box', function () {
    Route::get('/_layout', fn () => View::make('layouts.front', ['slot' => 'hello']));
    $this->get('/_layout')->assertOk()
        ->assertSee('laradoc.theme.front', false)
        ->assertSee('Toggle dark mode', false)
        ->assertSee('name="q"', false)
        ->assertSee('hello');
});
