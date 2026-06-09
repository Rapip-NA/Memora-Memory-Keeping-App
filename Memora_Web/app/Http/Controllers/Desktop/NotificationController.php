<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Bookmark;

class NotificationController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $notifications = Notification::where('user_id', $currentUser->id)->latest()->get();
        
        // Mark as read
        Notification::where('user_id', $currentUser->id)->whereNull('read_at')->update(['read_at' => now()]);
        
        return view('desktop.notification', compact('currentUser', 'notifications'));
    }

    public function count()
    {
        $currentUser = auth()->user();
        $count = Notification::where('user_id', $currentUser->id)->whereNull('read_at')->count();
        return response()->json(['count' => $count]);
    }

    public function bookmarks()
    {
        $currentUser = auth()->user();
        $bookmarks = Bookmark::where('user_id', $currentUser->id)
            ->with(['post.user', 'post.comments.user', 'post.likes', 'post.bookmarks'])
            ->latest()
            ->get();
        $posts = $bookmarks->pluck('post')->filter();
        return view('desktop.bookmarks', compact('posts', 'currentUser'));
    }
}
