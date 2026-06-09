<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostMinimalResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a paginated list of posts, optionally filtered by category.
     */
    public function index(Request $request)
    {
        $query = Post::with(['user', 'poll.options', 'poll.votes'])->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
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
            $file = $request->file('photo');
            $mimeType = $file->getMimeType();
            $isVideo = str_starts_with($mimeType, 'video/');
            $folder = $isVideo ? 'videos/posts' : 'photos/posts';
            $photoPath = $file->store($folder, 'public');
        }

        $post = Post::create([
            'user_id'  => auth()->id(),
            'content'  => $validated['content'],
            'photo'    => $photoPath,
            'category' => $validated['category'],
        ]);

        if ($photoPath) {
            \App\Jobs\ProcessMediaUpload::dispatch($post->id, [])->afterCommit();
        }

        // Handle Poll Creation
        if ($request->has('poll_options') && is_array($request->poll_options)) {
            $validOptions = array_filter($request->poll_options, function ($opt) {
                return !empty(trim($opt));
            });

            if (count($validOptions) >= 2) {
                $durationDays = $request->input('poll_duration_days', 1);
                $poll = \App\Models\Poll::create([
                    'post_id'    => $post->id,
                    'expires_at' => now()->addDays((int) $durationDays),
                ]);

                foreach ($validOptions as $optionText) {
                    \App\Models\PollOption::create([
                        'poll_id' => $poll->id,
                        'text'    => trim($optionText),
                    ]);
                }
            }
        }

        $post->load(['user', 'poll.options', 'poll.votes']);

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
            // Hapus foto/video lama jika ada
            if ($post->photo) {
                $decoded = json_decode($post->photo, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $p) {
                        Storage::disk('public')->delete($p);
                    }
                } else {
                    Storage::disk('public')->delete($post->photo);
                }
            }
            $file = $request->file('photo');
            $mimeType = $file->getMimeType();
            $isVideo = str_starts_with($mimeType, 'video/');
            $folder = $isVideo ? 'videos/posts' : 'photos/posts';
            $validated['photo'] = $file->store($folder, 'public');
        }

        $post->update($validated);

        if ($request->hasFile('photo')) {
            \App\Jobs\ProcessMediaUpload::dispatch($post->id, [])->afterCommit();
        }
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

    /**
     * Vote on a poll option (mobile).
     * POST /api/posts/{id}/poll/{pollId}/vote
     */
    public function votePoll(Request $request, $id, $pollId)
    {
        $request->validate(['option_id' => 'required|exists:poll_options,id']);

        $poll = Poll::with(['options', 'votes'])->find($pollId);

        if (!$poll || $poll->post_id != $id) {
            return response()->json(['status' => 'error', 'message' => 'Polling tidak ditemukan.'], 404);
        }

        if ($poll->is_expired) {
            return response()->json(['status' => 'error', 'message' => 'Polling sudah berakhir.'], 403);
        }

        $user = auth()->user();
        if ($poll->votes->where('user_id', $user->id)->count() > 0) {
            return response()->json(['status' => 'error', 'message' => 'Kamu sudah memilih.'], 403);
        }

        PollVote::create([
            'poll_id'        => $poll->id,
            'user_id'        => $user->id,
            'poll_option_id' => $request->option_id,
        ]);

        PollOption::where('id', $request->option_id)->increment('votes_count');

        // Return updated poll data
        $poll->load('options', 'votes');
        $totalVotes = $poll->votes->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_votes' => $totalVotes,
                'options'     => $poll->options->map(fn ($opt) => [
                    'id'          => $opt->id,
                    'text'        => $opt->text,
                    'votes_count' => $opt->votes_count,
                    'percent'     => $totalVotes > 0
                        ? round(($opt->votes_count / $totalVotes) * 100)
                        : 0,
                ])->values(),
            ],
        ]);
    }

    /**
     * Display a paginated list of posts bookmarked by the user.
     */
    public function bookmarks(Request $request)
    {
        $user = auth()->user();

        $posts = Post::whereHas('bookmarks', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['user', 'poll.options', 'poll.votes'])
        ->latest()
        ->paginate(15);

        return PostMinimalResource::collection($posts)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Bookmark or un-bookmark a post.
     */
    public function bookmark($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Post tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();
        $existingBookmark = Bookmark::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existingBookmark) {
            $existingBookmark->delete();
            return response()->json([
                'status'     => 'success',
                'bookmarked' => false,
                'message'    => 'Bookmark dihapus.',
            ]);
        }

        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'status'     => 'success',
            'bookmarked' => true,
            'message'    => 'Post berhasil disimpan ke bookmark.',
        ]);
    }
}
