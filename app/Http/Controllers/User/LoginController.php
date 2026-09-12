<?php

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email ou senha errado']);
        }

        return redirect('/');
    }
}
