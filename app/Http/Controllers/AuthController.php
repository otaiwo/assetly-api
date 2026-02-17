<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // REGISTER
    
public function register(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[@$!%*?&#]/',
        ],
    ]);

DB::beginTransaction(); // ensures no half-registered users

try {
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => ($validated['password']),
        'balance' => 0,
    ]);

    // assign role via Spatie
    $user->assignRole('buyer');

    // send verification email
    $user->sendEmailVerificationNotification();

    // create token
    $deviceName = $request->header('User-Agent') ?? 'unknown device';
    $token = $user->createToken($deviceName, ['*'])->plainTextToken;

    DB::commit();

    return response()->json([
        'message' => 'User registered successfully. Please verify your email.',
        'user' => $user,
        'token' => $token,
    ], 201);

} catch (\Throwable $e) {
    DB::rollBack();
    return response()->json([
        'success' => false,
        'message' => 'Registration failed',
        'error' => $e->getMessage(),
    ], 500);
}
}

    // LOGIN
public function login(Request $request)
{
  
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = User::where('email', $validated['email'])->first();

    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        // Log failed attempt (without rate limiting)
        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'time' => now(),
        ]);

        throw ValidationException::withMessages([
            'email' => ['Invalid credentials'],
        ]);
    }

    // Log successful login
    Log::info('Successful login', [
        'email' => $user->email,
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
        'time' => now(),
    ]);

    // Optional: revoke old tokens
    $user->tokens()->delete();

    $deviceName = $request->header('User-Agent') ?? 'unknown device';
    $token = $user->createToken($deviceName)->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token,
    ]);
}


    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // GET AUTHENTICATED USER
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
