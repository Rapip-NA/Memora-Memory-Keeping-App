<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the classrooms.
     * Accessible to all authenticated users.
     */
    public function index(Request $request): JsonResponse
    {
        $classrooms = Classroom::orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data'   => ClassroomResource::collection($classrooms),
        ]);
    }

    /**
     * Store a newly created classroom in storage.
     * Admin only.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:classrooms,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique'   => 'Nama kelas sudah terdaftar.',
            'name.max'      => 'Nama kelas maksimal 100 karakter.',
            'description.max' => 'Deskripsi kelas maksimal 500 karakter.',
        ]);

        $classroom = Classroom::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil ditambahkan',
            'data'    => new ClassroomResource($classroom),
        ], 201);
    }

    /**
     * Update the specified classroom in storage.
     * Admin only.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:classrooms,name,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique'   => 'Nama kelas sudah terdaftar.',
            'name.max'      => 'Nama kelas maksimal 100 karakter.',
            'description.max' => 'Deskripsi kelas maksimal 500 karakter.',
        ]);

        $classroom->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil diperbarui',
            'data'    => new ClassroomResource($classroom),
        ]);
    }

    /**
     * Remove the specified classroom from storage.
     * Admin only.
     */
    public function destroy(int $id): JsonResponse
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        $classroom->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil dihapus',
        ]);
    }
}
