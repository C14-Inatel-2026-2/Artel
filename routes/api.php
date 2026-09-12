<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas da API REST do Artel para gestão de Grupos e Participantes.
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
});
