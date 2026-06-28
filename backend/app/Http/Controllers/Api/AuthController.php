<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $key = 'admin-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.'], 429);
        }

        $admin = User::where('email', $request->email)->where('role', 'admin')->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($key, 300);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        RateLimiter::clear($key);
        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($admin),
        ]);
    }

    public function csLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $key = 'cs-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.'], 429);
        }

        $staff = User::where('email', $request->email)->where('role', 'cs')->first();

        if (! $staff || ! Hash::check($request->password, $staff->password)) {
            RateLimiter::hit($key, 300);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        RateLimiter::clear($key);
        $token = $staff->createToken('cs-token', ['cs'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($staff),
        ]);
    }

    public function clientLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $key = 'client-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.'], 429);
        }

        // Allow login with brand email (john@lacouture.com) OR personal email
        $client = User::where('role', 'client')
                      ->where(function ($q) use ($request) {
                          $q->where('email', $request->email)
                            ->orWhere('brand_email', $request->email);
                      })
                      ->first();

        if (! $client || ! Hash::check($request->password, $client->password)) {
            RateLimiter::hit($key, 300);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($client->status !== 'approved') {
            $msg = $client->status === 'pending'
                ? 'Your account is pending approval. You will be notified by email.'
                : 'Your account has been rejected. Contact us for more information.';
            return response()->json(['message' => $msg], 403);
        }

        RateLimiter::clear($key);
        $token = $client->createToken('client-token', ['client'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($client),
        ]);
    }

    public function clientRegister(Request $request)
    {
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => 'Too many registrations. Try again in ' . RateLimiter::availableIn($key) . ' seconds.'], 429);
        }

        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|max:255|unique:users,email|unique:users,brand_email',
            'phone'     => 'nullable|string|max:20',
            'password'  => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-#])[A-Za-z\d@$!%*?&_\-#]{8,}$/',
            ],
            'interests' => 'nullable|string|max:1000',
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, a number, and a special character (@$!%*?&_-#).',
        ]);

        RateLimiter::hit($key, 3600);

        // Generate brand email from first name: john@lacouture.com
        $firstName  = strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', trim($request->name))[0]));
        $firstName  = $firstName ?: 'client';
        $brandEmail = $this->generateBrandEmail($firstName);

        $client = User::create([
            'name'           => strip_tags($request->name),
            'email'          => $request->email,
            'brand_email'    => $brandEmail,
            'personal_email' => $request->email,
            'phone'          => $request->phone ? preg_replace('/[^0-9+\-\s()]/', '', $request->phone) : null,
            'password'       => Hash::make($request->password),
            'role'           => 'client',
            'status'         => 'pending',
            'interests'      => $request->interests ? strip_tags($request->interests) : null,
        ]);

        return response()->json([
            'message'     => 'Registration successful! Awaiting staff approval.',
            'brand_login' => $brandEmail,
            'user'        => [
                'id'          => $client->id,
                'name'        => $client->name,
                'email'       => $client->email,
                'brand_email' => $client->brand_email,
            ],
        ], 201);
    }

    private function generateBrandEmail(string $firstName): string
    {
        $base  = $firstName . '@lacouture.com';
        if (! User::where('brand_email', $base)->exists() && ! User::where('email', $base)->exists()) {
            return $base;
        }
        $i = 2;
        while (true) {
            $candidate = $firstName . $i . '@lacouture.com';
            if (! User::where('brand_email', $candidate)->exists() && ! User::where('email', $candidate)->exists()) {
                return $candidate;
            }
            $i++;
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json($this->userPayload($user, true));
    }

    private function userPayload(User $user, bool $full = false): array
    {
        $base = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'brand_email' => $user->brand_email,
            'phone'       => $user->phone,
            'role'        => $user->role,
            'status'      => $user->status,
        ];

        if ($full) {
            $base['interests']   = $user->interests;
            $base['approved_at'] = $user->approved_at;
            $base['created_at']  = $user->created_at;
        }

        return $base;
    }
}
