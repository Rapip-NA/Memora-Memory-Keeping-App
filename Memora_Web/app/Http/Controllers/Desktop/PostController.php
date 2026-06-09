<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Bookmark;
use App\Models\Notification;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostController extends Controller
{
    public function feed(Request $request)
    {
        $posts = Post::with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])->latest()->paginate(10);
        $currentUser = auth()->user();

        if ($request->ajax()) {
            $view = view('desktop.partials.post-list', compact('posts', 'currentUser'))->render();
            return response()->json(['html' => $view, 'next_page' => $posts->nextPageUrl()]);
        }
        
        return view('desktop.feed', compact('posts', 'currentUser'));
    }

    public function show($id)
    {
        $post = Post::with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])->findOrFail($id);
        $currentUser = auth()->user();
        return view('desktop.post-show', compact('post', 'currentUser'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required_without_all:photos,poll_options|nullable|string',
            'photos'  => 'nullable|array|max:10',
            'photos.*'=> 'file|max:102400|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/3gpp,video/x-matroska,video/mpeg,video/ogg',
            'event_name' => 'nullable|string',
            'event_date' => 'nullable|date',
            'event_time' => 'nullable|string',
            'event_location' => 'nullable|string'
        ]);

        $user = auth()->user();

        $mediaPaths = [];
        $hasFiles = false;
        
        if ($request->hasFile('photos')) {
            $hasFiles = true;
            foreach ($request->file('photos') as $file) {
                $mimeType = $file->getMimeType();
                $isVideo = str_starts_with($mimeType, 'video/');
                $folder = $isVideo ? 'videos/posts' : 'photos/posts';
                $filename = Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
                
                // Simpan langsung ke public storage agar post langsung muncul (Instant UI)
                $path = $file->storeAs($folder, $filename, 'public');
                $mediaPaths[] = $path;
            }
        }

        $content = $request->content ?? '';
        if ($request->event_name) {
            $eventDateTime = null;
            if ($request->event_date) {
                $eventDateTime = $request->event_date;
                if ($request->event_time) {
                    $eventDateTime .= ' ' . $request->event_time . ':00';
                } else {
                    $eventDateTime .= ' 00:00:00';
                }
            }

            $content .= "\n\n🎉 Acara: " . $request->event_name;
            if ($request->event_date) {
                $content .= "\n📅 Tanggal: " . Carbon::parse($request->event_date)->translatedFormat('d F Y') . ($request->event_time ? ' Jam ' . $request->event_time : '');
            }
            if ($request->event_location) {
                $content .= "\n📍 Lokasi: " . $request->event_location;
            }

            if ($eventDateTime) {
                Event::create([
                    'title' => $request->event_name,
                    'description' => $content,
                    'event_date' => $eventDateTime,
                    'location' => $request->event_location ?? 'TBA',
                    'created_by' => $user->id,
                ]);
            }
        }

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $content,
            'photo' => $hasFiles ? json_encode($mediaPaths) : null,
            'category' => 'lainnya',
        ]);
        
        // Handle Poll Creation
        if ($request->has('poll_options') && is_array($request->poll_options)) {
            $validOptions = array_filter($request->poll_options, function($opt) {
                return !empty(trim($opt));
            });
            
            if (count($validOptions) >= 2) {
                $durationDays = $request->input('poll_duration_days', 1);
                $poll = \App\Models\Poll::create([
                    'post_id' => $post->id,
                    'expires_at' => now()->addDays((int) $durationDays),
                ]);
                
                foreach ($validOptions as $optionText) {
                    \App\Models\PollOption::create([
                        'poll_id' => $poll->id,
                        'text' => trim($optionText),
                    ]);
                }
            }
        }
        
        if ($hasFiles) {
            // Job sekarang hanya mengurus kompresi background dan backup ke cloud (R2)
            \App\Jobs\ProcessMediaUpload::dispatch($post->id, [])->afterCommit();
        }

        if ($request->ajax() || $request->wantsJson()) {
            $currentUser = $user;
            // Muat relasi yang diperlukan untuk view post-card
            $post->load(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes']);
            
            $postHtml = view('components.post-card', [
                'post' => $post, 
                'currentUser' => $currentUser
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Post berhasil dibuat.',
                'post' => $post,
                'html' => $postHtml,
            ]);
        }

        $msg = $hasFiles ? 'Postingan sedang diproses' : 'Post berhasil dibuat.';
        return redirect()->route('desktop.feed')->with('success', $msg);
    }

    public function like($id)
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        $existingLike = $post->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            $existingLike->delete();
            $post->decrement('likes_count');
            return response()->json(['liked' => false, 'likes_count' => $post->likes_count]);
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $post->increment('likes_count');
            
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'type' => 'like',
                    'data' => [
                        'message' => $user->name . ' menyukai postinganmu.',
                        'post_id' => $post->id,
                        'actor_name' => $user->name,
                        'actor_photo' => $user->photo,
                    ]
                ]);
            }
            
            return response()->json(['liked' => true, 'likes_count' => $post->likes_count]);
        }
    }

    public function votePoll(Request $request, $id)
    {
        $request->validate(['option_id' => 'required|exists:poll_options,id']);
        
        $poll = \App\Models\Poll::findOrFail($id);
        $user = auth()->user();
        
        if ($poll->is_expired) {
            return response()->json(['error' => 'Polling sudah berakhir.'], 403);
        }
        
        $existingVote = $poll->votes()->where('user_id', $user->id)->first();
        if ($existingVote) {
            return response()->json(['error' => 'Anda sudah memberikan suara pada polling ini.'], 403);
        }
        
        \App\Models\PollVote::create([
            'poll_id' => $poll->id,
            'user_id' => $user->id,
            'poll_option_id' => $request->option_id,
        ]);
        
        \App\Models\PollOption::where('id', $request->option_id)->increment('votes_count');
        
        // Return updated poll data
        $poll->load('options');
        $totalVotes = $poll->total_votes;
        
        $options = $poll->options->map(function($opt) use ($totalVotes) {
            $percent = $totalVotes > 0 ? round(($opt->votes_count / $totalVotes) * 100) : 0;
            return [
                'id' => $opt->id,
                'text' => $opt->text,
                'votes' => $opt->votes_count,
                'percent' => $percent
            ];
        });
        
        return response()->json([
            'success' => true,
            'total_votes' => $totalVotes,
            'options' => $options
        ]);
    }

    public function comment(Request $request, $id)
    {
        $request->validate(['body' => 'required|string']);
        $post = Post::findOrFail($id);
        $user = auth()->user();

        $post->comments()->create([
            'user_id' => $user->id,
            'body'    => $request->body,
        ]);

        if ($post->user_id !== $user->id) {
            Notification::create([
                'user_id' => $post->user_id,
                'type' => 'comment',
                'data' => [
                    'message' => $user->name . ' mengomentari postinganmu: "' . Str::limit($request->body, 30) . '"',
                    'post_id' => $post->id,
                    'actor_name' => $user->name,
                    'actor_photo' => $user->photo,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        if ($post->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['content' => 'required|string']);
        $post->update(['content' => $request->content]);

        return redirect()->back()->with('success', 'Post berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        if ($post->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($post->photo && $post->photo !== '[]') {
            $photos = json_decode($post->photo, true);
            if(is_array($photos)) {
                foreach($photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            } else {
                Storage::disk('public')->delete($post->photo);
            }
        }
        $post->delete();

        return redirect()->back()->with('success', 'Post berhasil dihapus.');
    }

    public function bookmark($id)
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        $existingBookmark = Bookmark::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($existingBookmark) {
            $existingBookmark->delete();
            return response()->json(['bookmarked' => false]);
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id
            ]);
            return response()->json(['bookmarked' => true]);
        }
    }
}
