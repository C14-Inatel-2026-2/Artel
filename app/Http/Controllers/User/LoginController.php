<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->credentials())) {
            return back()->withErrors(['email' => 'Email ou senha errado']);
        }

        return redirect('/');
    }
}
