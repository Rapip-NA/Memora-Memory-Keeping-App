<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\GalleryPhoto;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * List comments for a post.
     */
    public function indexForPost(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        $comments = Comment::where('commentable_type', Post::class)
            ->where('commentable_id', $postId)
            ->with('user')
            ->latest()
            ->paginate(20);

        return CommentResource::collection($comments)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a comment on a post.
     */
    public function storeForPost(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'body' => 'required|string|max:500',
        ], [
            'body.required' => 'Isi komentar tidak boleh kosong.',
            'body.string'   => 'Komentar harus berupa teks.',
            'body.max'      => 'Komentar tidak boleh lebih dari 500 karakter.',
        ]);

        $comment = Comment::create([
            'user_id'          => auth()->id(),
            'commentable_type' => Post::class,
            'commentable_id'   => $postId,
            'body'             => $request->body,
        ]);

        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List comments for a gallery photo.
     */
    public function indexForGallery(Request $request, $photoId)
    {
        $photo = GalleryPhoto::find($photoId);

        if (! $photo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        $comments = Comment::where('commentable_type', GalleryPhoto::class)
            ->where('commentable_id', $photoId)
            ->with('user')
            ->latest()
            ->paginate(20);

        return CommentResource::collection($comments)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a comment on a gallery photo.
     */
    public function storeForGallery(Request $request, $photoId)
    {
        $photo = GalleryPhoto::find($photoId);

        if (! $photo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'body' => 'required|string|max:500',
        ], [
            'body.required' => 'Isi komentar tidak boleh kosong.',
            'body.string'   => 'Komentar harus berupa teks.',
            'body.max'      => 'Komentar tidak boleh lebih dari 500 karakter.',
        ]);

        $comment = Comment::create([
            'user_id'          => auth()->id(),
            'commentable_type' => GalleryPhoto::class,
            'commentable_id'   => $photoId,
            'body'             => $request->body,
        ]);

        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Soft delete a comment (owner or admin).
     */
    public function destroy(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Komentar tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();

        if ($comment->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki izin untuk menghapus komentar ini.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }
}
