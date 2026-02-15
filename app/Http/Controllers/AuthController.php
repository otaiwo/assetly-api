<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

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

        // ✅ Create user (password is auto-hashed via model)
     $user = User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => $validated['password'],
]);
$user->assignRole('buyer');

        // ⚡ Send email verification
        $user->sendEmailVerificationNotification();

        // ✅ Create token with device metadata
        $deviceName = $request->header('User-Agent') ?? 'unknown device';
        $token = $user->createToken($deviceName, ['*'])->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully. Please verify your email.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // LOGIN
public function login(Request $request)
{
    $emailOrIp = $request->ip(); // or $request->email for per-user lockout

    // Check lockout
    if (RateLimiter::tooManyAttempts($emailOrIp, 5)) { // 5 failed attempts
        throw ValidationException::withMessages([
            'email' => ['Too many login attempts. Try again in ' . RateLimiter::availableIn($emailOrIp) . ' seconds.'],
        ]);
    }

    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = User::where('email', $validated['email'])->first();

    // Failed login
    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        RateLimiter::hit($emailOrIp, 60); // increment failed attempts

        // Log failed attempt
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

    // Successful login resets failed attempts
    RateLimiter::clear($emailOrIp);

    // Log successful login
    Log::info('Successful login', [
        'email' => $user->email,
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
        'time' => now(),
    ]);

    // Optional: revoke old tokens for single active session
    $user->tokens()->delete();

    // Create token with device info
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
