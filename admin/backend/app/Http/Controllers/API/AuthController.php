<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->email;
        $user = User::with('student')
            ->where('email', $login)
            ->orWhereHas('student', function($query) use ($login) {
                $query->where('phone', $login);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Login yoki parol noto\'g\'ri.'],
            ]);
        }

        if ($user->access_expires_at && $user->access_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Sizning kirish muddatingiz tugagan. Iltimos admin bilan bog\'laning.'],
            ]);
        }

        // Validate role if requested
        if ($request->has('role') && $user->role !== $request->role) {
            throw ValidationException::withMessages([
                'email' => ['Sizda ushbu tizimga kirish huquqi yo\'q.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Muvaqqiyatli chiqildi'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('student.organization', 'student.group'));
    }
}
