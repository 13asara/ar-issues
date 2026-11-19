<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! hash_equals((string) config('admin.password'), $data['password'])) {
            return back()->withErrors(['password' => 'Неверный пароль']);
        }

        $request->session()->put('admin_authenticated', true);

        return redirect()->route('admin.issues.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_authenticated');

        return redirect()->route('admin.login');
    }
}
