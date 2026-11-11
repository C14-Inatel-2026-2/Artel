<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupBalanceController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas da API REST do Artel.
|
*/

Route::prefix('groups')->name('groups.')->group(function () {
    Route::get('/', [GroupController::class, 'index'])->name('index');
    Route::post('/', [GroupController::class, 'store'])->name('store');
    Route::get('/{group}', [GroupController::class, 'show'])->name('show');
    Route::put('/{group}', [GroupController::class, 'update'])->name('update');
    Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');

    // Gestão de participantes do grupo
    Route::get('/{group}/members', [GroupMemberController::class, 'index'])->name('members.index');
    Route::post('/{group}/members', [GroupMemberController::class, 'store'])->name('members.store');
    Route::delete('/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('members.destroy');

    // Despesas e balanço do grupo
    Route::get('/{group}/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/{group}/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/{group}/balances', [GroupBalanceController::class, 'index'])->name('balances.index');

    // Quitações (acertos) entre participantes
    Route::get('/{group}/settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::post('/{group}/settlements', [SettlementController::class, 'store'])->name('settlements.store');
});

Route::prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show');
    Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
    Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
});
