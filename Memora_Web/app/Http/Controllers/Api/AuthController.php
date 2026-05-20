<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new member.
     * Status default: 'pending' — menunggu persetujuan admin.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'member',
            'status'   => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pendaftaran berhasil, menunggu persetujuan admin',
            'data'    => [
                'user' => $user,
            ],
        ], 201);
    }

    /**
     * Login dan dapatkan Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Cek credentials
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Cek status akun
        if ($user->status === 'pending') {
            Auth::logout();

            return response()->json([
                'status'  => 'error',
                'message' => 'Akun kamu belum disetujui admin',
            ], 403);
        }

        if ($user->status === 'inactive') {
            Auth::logout();

            return response()->json([
                'status'  => 'error',
                'message' => 'Akun kamu dinonaktifkan',
            ], 403);
        }

        // Hapus token lama agar tidak menumpuk (opsional, tapi best-practice)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'token' => $token,
                'user'  => new UserResource($user),
            ],
        ]);
    }

    /**
     * Logout — hapus token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Kembalikan data user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }
}
