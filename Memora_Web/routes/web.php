<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Desktop\PostController;
use App\Http\Controllers\Desktop\EventController;
use App\Http\Controllers\Desktop\ProfileController;
use App\Http\Controllers\Desktop\GalleryController;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('desktop.feed');
    }
    return view('welcome');
});

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('guest');
Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegisterForm'])->name('register')->middleware('guest');
Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register'])->middleware('guest');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {

Route::get('/desktop/profile', [ProfileController::class, 'index'])->name('desktop.profile');
Route::put('/desktop/profile', [ProfileController::class, 'update'])->name('desktop.profile.update');

Route::get('/desktop/feed', [PostController::class, 'feed'])->name('desktop.feed');

Route::get('/desktop/explore', function (\Illuminate\Http\Request $request) {
    $currentUser = auth()->user() ?? \App\Models\User::first();
    $q = $request->input('q');
    
    $users = collect();
    $posts = collect();
    
    if ($q) {
        $users = \App\Models\User::where('name', 'like', "%$q%")->orWhere('bio', 'like', "%$q%")->get();
        $posts = \App\Models\Post::where('content', 'like', "%$q%")->with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])->latest()->get();
    } else {
        $posts = \App\Models\Post::with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])->latest()->limit(20)->get();
    }
    
    return view('desktop.explore', compact('currentUser', 'users', 'posts', 'q'));
})->name('desktop.explore');

Route::get('/desktop/dashboard', function () {
    return view('desktop.dashboard');
})->name('desktop.dashboard');

Route::get('/desktop/events', [EventController::class, 'index'])->name('desktop.events');
Route::get('/desktop/events/{id}', [EventController::class, 'show'])->name('desktop.events.show');
Route::post('/desktop/events/{id}/rsvp', [EventController::class, 'rsvp'])->name('desktop.events.rsvp');
Route::post('/desktop/events/{id}/comment', [EventController::class, 'comment'])->name('desktop.events.comment');

Route::get('/desktop/dashboard/data', function () {
    $totalUsers = \App\Models\User::count();
    $totalPhotos = \App\Models\GalleryPhoto::count();
    $totalPosts = \App\Models\Post::count();
    
    $events = \App\Models\Post::where('content', 'like', '%Acara:%')->orWhere('content', 'like', '%acara%')->count();
    $reunions = \App\Models\Post::where('content', 'like', '%reuni%')->count();

    $distribution = [
        max(0, $totalPosts - $events - $reunions),
        $reunions,
        $events,
        $totalPhotos
    ];

    $months = [];
    $activityPhotos = [];
    $activityPosts = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $months[] = $date->translatedFormat('M');
        
        $photosCount = \App\Models\GalleryPhoto::whereYear('created_at', $date->year)
                                               ->whereMonth('created_at', $date->month)
                                               ->count();
                                               
        $postsCount = \App\Models\Post::whereYear('created_at', $date->year)
                                      ->whereMonth('created_at', $date->month)
                                      ->count();
                                      
        $activityPhotos[] = $photosCount;
        $activityPosts[] = $postsCount;
    }

    return response()->json([
        'stats' => [
            'users' => $totalUsers,
            'photos' => $totalPhotos,
            'posts' => $totalPosts,
            'events' => $events,
            'reunions' => $reunions
        ],
        'distribution' => $distribution,
        'activity' => [
            'labels' => $months,
            'photos' => $activityPhotos,
            'posts' => $activityPosts
        ]
    ]);
})->name('desktop.dashboard.data');

Route::get('/desktop/notification', function () {
    $currentUser = auth()->user() ?? \App\Models\User::first();
    $notifications = \App\Models\Notification::where('user_id', $currentUser->id)->latest()->get();
    
    // Mark as read
    \App\Models\Notification::where('user_id', $currentUser->id)->whereNull('read_at')->update(['read_at' => now()]);
    
    return view('desktop.notification', compact('currentUser', 'notifications'));
})->name('desktop.notification');

Route::get('/desktop/notifications/count', function () {
    $currentUser = auth()->user() ?? \App\Models\User::first();
    $count = \App\Models\Notification::where('user_id', $currentUser->id)->whereNull('read_at')->count();
    return response()->json(['count' => $count]);
})->name('desktop.notifications.count');

Route::get('/desktop/gallery', [GalleryController::class, 'index'])->name('desktop.gallery');

Route::post('/desktop/post', [PostController::class, 'store'])->name('desktop.post.store');

Route::post('/desktop/poll/{id}/vote', [PostController::class, 'votePoll'])->name('desktop.poll.vote');

Route::post('/desktop/post/{id}/like', [PostController::class, 'like'])->name('desktop.post.like');

Route::post('/desktop/post/{id}/comment', [PostController::class, 'comment'])->name('desktop.post.comment');

Route::put('/desktop/post/{id}', [PostController::class, 'update'])->name('desktop.post.update');

Route::delete('/desktop/post/{id}', [PostController::class, 'destroy'])->name('desktop.post.destroy');

Route::post('/desktop/post/{id}/bookmark', [PostController::class, 'bookmark'])->name('desktop.post.bookmark');

Route::get('/desktop/bookmarks', function () {
    $currentUser = auth()->user() ?? \App\Models\User::first();
    $bookmarks = \App\Models\Bookmark::where('user_id', $currentUser->id)->with(['post.user', 'post.comments.user', 'post.likes', 'post.bookmarks'])->latest()->get();
    // We can extract posts from bookmarks or just pass bookmarks
    $posts = $bookmarks->pluck('post')->filter();
    return view('desktop.bookmarks', compact('posts', 'currentUser'));
})->name('desktop.bookmarks');

Route::post('/desktop/gallery', [GalleryController::class, 'store'])->name('desktop.gallery.store');
Route::put('/desktop/gallery/{id}', [GalleryController::class, 'update'])->name('desktop.gallery.update');
Route::delete('/desktop/gallery/{id}', [GalleryController::class, 'destroy'])->name('desktop.gallery.destroy');
});
