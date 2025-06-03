<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function register(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user, // Mengirim data user
            'token' => $token, // Menggunakan key 'token'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        // $user->tokens()->delete(); // Opsional: hapus token lama
        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,      // KIRIM DATA USER
            'token' => $token,    // GUNAKAN KEY 'token'
            'token_type' => 'Bearer' // Opsional
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
