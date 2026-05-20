<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    /**
     * Toggle like/unlike on a post.
     */
    public function togglePost(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (! $post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        $userId = auth()->id();

        $result = DB::transaction(function () use ($post, $userId) {
            $existingLike = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->first();

            if ($existingLike) {
                // Sudah like → hapus, decrement (min 0)
                $existingLike->delete();
                $post->decrement('likes_count');
                $post->likes_count = max(0, $post->likes_count);
                $post->save();

                return [
                    'liked'       => false,
                    'likes_count' => $post->fresh()->likes_count,
                ];
            }

            // Belum like → buat baru, increment
            Like::create([
                'user_id'       => $userId,
                'likeable_type' => Post::class,
                'likeable_id'   => $post->id,
            ]);
            $post->increment('likes_count');

            return [
                'liked'       => true,
                'likes_count' => $post->fresh()->likes_count,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ], 200);
    }
}
