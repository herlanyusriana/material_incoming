<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim((string) $request->input('username', ''));
        $login = $username !== '' ? $username : trim((string) $request->input('login', ''));

        if ($login === '') {
            throw ValidationException::withMessages([
                'username' => ['Username wajib diisi.'],
            ]);
        }

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        /** @var User|null $user */
        $user = User::query()->where($field, $login)->first();

        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        $deviceName = $request->header('X-Device-Name') ?: ($request->userAgent() ?: 'android');

        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role ?? null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}
