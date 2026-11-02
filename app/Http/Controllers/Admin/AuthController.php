<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function form() { return view('admin.login'); }

    public function login(Request $r)
    {
        $in = $r->validate(['login' => 'required|string|max:120', 'password' => 'required|string']);
        $login = trim($in['login']);
        $field = str_contains($login, '@') ? 'email' : 'username';
        $cred = [$field => $login, 'password' => $in['password']];
        $key = 'admin-login|' . strtolower($login) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.'])->onlyInput('login');
        }
        if (!Auth::attempt($cred, $r->boolean('remember'))) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['login' => 'Username/email atau kata sandi salah.'])->onlyInput('login');
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
