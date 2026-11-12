<?php

use App\Http\Controllers\ExpensePageController;
use App\Http\Controllers\GroupPageController;
use App\Http\Controllers\MemberPageController;
use App\Http\Controllers\SettlementPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('groups')->name('web.groups.')->group(function () {
    Route::get('/', [GroupPageController::class, 'index'])->name('index');
    Route::post('/', [GroupPageController::class, 'store'])->name('store');
    Route::get('/{group}', [GroupPageController::class, 'show'])->name('show');
    Route::post('/{group}/members', [MemberPageController::class, 'store'])->name('members.store');
    Route::post('/{group}/expenses', [ExpensePageController::class, 'store'])->name('expenses.store');
    Route::post('/{group}/settlements', [SettlementPageController::class, 'store'])->name('settlements.store');
});

Route::middleware('auth')->prefix('expenses')->name('web.expenses.')->group(function () {
    Route::put('/{expense}', [ExpensePageController::class, 'update'])->name('update');
    Route::delete('/{expense}', [ExpensePageController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/user.php';
