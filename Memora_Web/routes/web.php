<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Desktop\PostController;
use App\Http\Controllers\Desktop\EventController;
use App\Http\Controllers\Desktop\ProfileController;
use App\Http\Controllers\Desktop\GalleryController;
use App\Http\Controllers\Desktop\DashboardController;
use App\Http\Controllers\Desktop\AdminController;

use App\Http\Controllers\Desktop\ExploreController;
use App\Http\Controllers\Desktop\NotificationController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('desktop.feed');
    }
    return view('welcome');
});

// ── Public storage file serving (bypass Windows junction issue) ──────────────
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/' . $path);
    if (! file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*')->name('storage.serve');

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']);
Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegisterForm'])->name('register')->middleware('guest');
Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register'])->middleware(['guest', 'throttle:10,1']);
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {

    // Rute untuk pengguna yang sedang menunggu persetujuan (pending)
    Route::get('/pending', function () {
        return view('auth.pending');
    })->name('pending');

    // Rute yang diproteksi agar hanya pengguna aktif yang bisa masuk
    Route::middleware('check.status')->group(function () {
        Route::get('/desktop/profile', [ProfileController::class, 'index'])->name('desktop.profile');
        Route::put('/desktop/profile', [ProfileController::class, 'update'])->name('desktop.profile.update');

        Route::get('/desktop/feed', [PostController::class, 'feed'])->name('desktop.feed');
        Route::get('/desktop/post/{id}', [PostController::class, 'show'])->name('desktop.post.show');

        Route::get('/desktop/explore', [ExploreController::class, 'index'])->name('desktop.explore');
        Route::get('/desktop/users', [ExploreController::class, 'users'])->name('desktop.users');

        Route::get('/desktop/events', [EventController::class, 'index'])->name('desktop.events');
        Route::get('/desktop/events/{id}', [EventController::class, 'show'])->name('desktop.events.show');
        Route::post('/desktop/events/{id}/rsvp', [EventController::class, 'rsvp'])->name('desktop.events.rsvp');
        Route::post('/desktop/events/{id}/comment', [EventController::class, 'comment'])->name('desktop.events.comment');

        Route::get('/desktop/notification', [NotificationController::class, 'index'])->name('desktop.notification');
        Route::get('/desktop/notifications/count', [NotificationController::class, 'count'])->name('desktop.notifications.count');

        Route::get('/desktop/gallery', [GalleryController::class, 'index'])->name('desktop.gallery');

        Route::post('/desktop/post', [PostController::class, 'store'])->name('desktop.post.store');
        Route::post('/desktop/poll/{id}/vote', [PostController::class, 'votePoll'])->name('desktop.poll.vote');
        Route::post('/desktop/post/{id}/like', [PostController::class, 'like'])->name('desktop.post.like');
        Route::post('/desktop/post/{id}/comment', [PostController::class, 'comment'])->name('desktop.post.comment');
        Route::put('/desktop/post/{id}', [PostController::class, 'update'])->name('desktop.post.update');
        Route::delete('/desktop/post/{id}', [PostController::class, 'destroy'])->name('desktop.post.destroy');
        Route::post('/desktop/post/{id}/bookmark', [PostController::class, 'bookmark'])->name('desktop.post.bookmark');

        Route::get('/desktop/bookmarks', [NotificationController::class, 'bookmarks'])->name('desktop.bookmarks');

        Route::post('/desktop/gallery', [GalleryController::class, 'store'])->name('desktop.gallery.store');
        Route::put('/desktop/gallery/{id}', [GalleryController::class, 'update'])->name('desktop.gallery.update');
        Route::delete('/desktop/gallery/{id}', [GalleryController::class, 'destroy'])->name('desktop.gallery.destroy');
    });

    // Rute khusus Administrator
    Route::middleware('check.admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/dashboard/data', [DashboardController::class, 'data'])->name('admin.dashboard.data');
        Route::get('/admin/validation', [AdminController::class, 'validation'])->name('admin.validation');
        Route::post('/admin/validation/{id}/approve', [AdminController::class, 'approve'])->name('admin.validation.approve');
        Route::post('/admin/validation/{id}/reject', [AdminController::class, 'reject'])->name('admin.validation.reject');
        Route::get('/admin/monitoring', [AdminController::class, 'monitoring'])->name('admin.monitoring');
        Route::delete('/admin/monitoring/post/{id}', [AdminController::class, 'deletePost'])->name('admin.monitoring.deletePost');
        // Classroom Management
        Route::get('/admin/classrooms', [AdminController::class, 'classrooms'])->name('admin.classrooms');
        Route::post('/admin/classrooms', [AdminController::class, 'storeClassroom'])->name('admin.classrooms.store');
        Route::put('/admin/classrooms/{id}', [AdminController::class, 'updateClassroom'])->name('admin.classrooms.update');
        Route::delete('/admin/classrooms/{id}', [AdminController::class, 'destroyClassroom'])->name('admin.classrooms.destroy');
        // User Management
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    });
});
