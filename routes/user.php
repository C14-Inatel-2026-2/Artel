<?php

use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/user/register', [RegisterController::class, 'register']);
Route::post('/user/login', [LoginController::class, 'login']);
