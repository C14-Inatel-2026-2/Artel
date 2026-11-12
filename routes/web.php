<?php

use App\Http\Controllers\GroupPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('groups')->name('web.groups.')->group(function () {
    Route::get('/', [GroupPageController::class, 'index'])->name('index');
    Route::post('/', [GroupPageController::class, 'store'])->name('store');
    Route::get('/{group}', [GroupPageController::class, 'show'])->name('show');
});

require __DIR__.'/user.php';
