<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Daftar semua anggota aktif dengan filter & pagination.
     * GET /api/users?city=Jakarta&job=engineer&search=rafif&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('status', 'active');

        // Filter by city (like)
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Filter by job (like)
        if ($request->filled('job')) {
            $query->where('job', 'like', '%' . $request->job . '%');
        }

        // Search by name atau nickname
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nickname', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => UserMinimalResource::collection($users),
            'meta'   => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Data anggota aktif yang punya koordinat (untuk peta).
     * GET /api/users/map
     */
    public function map(Request $request): JsonResponse
    {
        $users = User::query()
            ->where('status', 'active')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => UserMinimalResource::collection($users),
        ]);
    }

    /**
     * Detail profil satu anggota aktif.
     * GET /api/users/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::where('id', $id)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anggota tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Update profil — hanya boleh edit profil SENDIRI.
     * PUT /api/users/{id}
     */
    public function update(UpdateProfileRequest $request, int $id): JsonResponse
    {
        // Otorisasi: hanya bisa edit profil sendiri
        if ($request->user()->id !== $id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk mengubah profil ini',
            ], 403);
        }

        $user = User::where('id', $id)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anggota tidak ditemukan',
            ], 404);
        }

        $user->update($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data'    => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    /**
     * Upload foto profil — hanya boleh upload foto SENDIRI.
     * POST /api/users/{id}/photo
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        // Otorisasi: hanya bisa upload foto sendiri
        if ($request->user()->id !== $id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk mengubah foto profil ini',
            ], 403);
        }

        $user = User::where('id', $id)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anggota tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp,heic,heif|max:2048',
        ], [
            'photo.required' => 'File foto wajib diunggah.',
            'photo.image'    => 'File harus berupa gambar.',
            'photo.mimes'    => 'Format foto harus JPG, PNG, WEBP, atau HEIC/HEIF.',
            'photo.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        // Hapus foto lama jika ada
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        // Simpan foto baru dengan nama unik
        $filename  = uniqid('photo_') . '.' . $request->file('photo')->getClientOriginalExtension();
        $path      = $request->file('photo')->storeAs('photos/profile', $filename, 'public');

        $user->update(['photo' => $path]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Foto profil berhasil diunggah',
            'data'    => [
                'photo_url' => Storage::url($path),
            ],
        ]);
    }

    /**
     * Upload foto banner — hanya boleh upload foto banner SENDIRI.
     * POST /api/users/{id}/banner-photo
     */
    public function uploadBannerPhoto(Request $request, int $id): JsonResponse
    {
        // Otorisasi: hanya bisa upload foto banner sendiri
        if ($request->user()->id !== $id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk mengubah foto banner ini',
            ], 403);
        }

        $user = User::where('id', $id)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anggota tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'banner_photo' => 'required|image|mimes:jpg,jpeg,png,webp,heic,heif|max:4096',
        ], [
            'banner_photo.required' => 'File foto banner wajib diunggah.',
            'banner_photo.image'    => 'File harus berupa gambar.',
            'banner_photo.mimes'    => 'Format foto banner harus JPG, PNG, WEBP, atau HEIC/HEIF.',
            'banner_photo.max'      => 'Ukuran foto banner maksimal 4MB.',
        ]);

        // Hapus foto banner lama jika ada
        if ($user->banner_photo && Storage::disk('public')->exists($user->banner_photo)) {
            Storage::disk('public')->delete($user->banner_photo);
        }

        // Simpan foto banner baru dengan nama unik
        $filename  = uniqid('banner_') . '.' . $request->file('banner_photo')->getClientOriginalExtension();
        $path      = $request->file('banner_photo')->storeAs('photos/profile/banners', $filename, 'public');

        $user->update(['banner_photo' => $path]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Foto banner berhasil diunggah',
            'data'    => [
                'banner_url' => Storage::url($path),
            ],
        ]);
    }
}
