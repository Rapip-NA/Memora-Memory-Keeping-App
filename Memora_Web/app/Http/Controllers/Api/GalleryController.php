<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryPhotoMinimalResource;
use App\Http\Resources\GalleryPhotoResource;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    /**
     * Display a paginated list of gallery photos.
     */
    public function index(Request $request)
    {
        $photos = GalleryPhoto::with('user')
            ->latest()
            ->paginate(20);

        return GalleryPhotoMinimalResource::collection($photos)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly uploaded gallery photo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'photo'   => 'required|image|mimes:jpg,jpeg,png,gif|max:5120',
            'caption' => 'nullable|string|max:300',
        ], [
            'photo.required' => 'Foto tidak boleh kosong.',
            'photo.image'    => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'    => 'Format foto harus jpg, jpeg, png, atau gif.',
            'photo.max'      => 'Ukuran foto tidak boleh lebih dari 5 MB.',
            'caption.max'    => 'Caption tidak boleh lebih dari 300 karakter.',
        ]);

        // Simpan dengan nama unik agar tidak overwrite
        $file      = $request->file('photo');
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath  = $file->storeAs('photos/gallery', $filename, 'public');

        $photo = GalleryPhoto::create([
            'user_id'         => auth()->id(),
            'file_path'       => $filePath,
            'caption'         => $request->caption,
            'tagged_user_ids' => [],
        ]);

        $photo->load('user');

        return (new GalleryPhotoResource($photo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified gallery photo.
     */
    public function show(Request $request, $id)
    {
        $photo = GalleryPhoto::with('user')->find($id);

        if (! $photo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        return new GalleryPhotoResource($photo);
    }

    /**
     * Tag users on a gallery photo (replaces existing tags).
     */
    public function tagUsers(Request $request, $id)
    {
        $photo = GalleryPhoto::find($id);

        if (! $photo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        if ($photo->user_id !== auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya pengunggah foto yang dapat menandai pengguna.',
            ], 403);
        }

        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ], [
            'user_ids.required'  => 'Daftar pengguna tidak boleh kosong.',
            'user_ids.array'     => 'user_ids harus berupa array.',
            'user_ids.*.exists'  => 'Salah satu pengguna yang ditandai tidak ditemukan.',
        ]);

        $photo->update([
            'tagged_user_ids' => $request->user_ids,
        ]);

        $photo->load('user');

        return new GalleryPhotoResource($photo);
    }

    /**
     * Soft delete a gallery photo and remove the file from storage.
     */
    public function destroy(Request $request, $id)
    {
        $photo = GalleryPhoto::find($id);

        if (! $photo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();

        if ($photo->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk menghapus foto ini.',
            ], 403);
        }

        // Hapus file fisik dari storage
        Storage::disk('public')->delete($photo->file_path);

        // Soft delete record
        $photo->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Foto berhasil dihapus.',
        ]);
    }
}
