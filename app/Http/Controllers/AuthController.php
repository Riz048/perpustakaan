<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Halaman login
    public function login()
    {
        return view('auth.login');
    }

    // Proses login user
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Catat ke log_login
            \DB::table('log_login')->insert([
                'user_id' => Auth::id(),
                'waktu_login' => now()
            ]);

            $user = Auth::user();

            if (in_array($user->role, ['petugas', 'kep_perpus', 'kepsek', 'admin'])) {
                return redirect()->route('dashboard');
            }

            return redirect('/');
        }

        return back()->withErrors([
            'login' => 'Username atau password salah.',
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
