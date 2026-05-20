<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostMinimalResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a paginated list of posts, optionally filtered by category.
     */
    public function index(Request $request)
    {
        $query = Post::with('user')->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $posts = $query->paginate(15);

        return PostMinimalResource::collection($posts)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos/posts', 'public');
        }

        $post = Post::create([
            'user_id'  => auth()->id(),
            'content'  => $validated['content'],
            'photo'    => $photoPath,
            'category' => $validated['category'],
        ]);

        $post->load('user');

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified post.
     */
    public function show(Request $request, $id)
    {
        $post = Post::with('user')->find($id);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        return new PostResource($post);
    }

    /**
     * Update the specified post (only by owner).
     */
    public function update(UpdatePostRequest $request, $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk mengubah post ini.',
            ], 403);
        }

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($post->photo) {
                Storage::disk('public')->delete($post->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos/posts', 'public');
        }

        $post->update($validated);
        $post->load('user');

        return new PostResource($post);
    }

    /**
     * Soft delete the specified post (owner or admin).
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();

        if ($post->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk menghapus post ini.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Post berhasil dihapus.',
        ]);
    }
}
