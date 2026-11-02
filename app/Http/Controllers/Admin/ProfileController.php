<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show() { return view('admin.profile'); }

    public function update(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username,' . $r->user()->id],
            'email' => 'required|email|unique:users,email,' . $r->user()->id,
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        $u = $r->user();
        if (!empty($d['password'])) {
            if (!Hash::check((string) ($d['current_password'] ?? ''), $u->password)) {
                return back()->withErrors(['current_password' => 'Kata sandi saat ini salah.'])->withInput();
            }
            $u->password = $d['password'];   // di-hash otomatis oleh cast
        }
        $u->name = $d['name'];
        $u->username = $d['username'];
        $u->email = $d['email'];
        $u->save();
        return back()->with('ok', 'Profil disimpan.');
    }
}
