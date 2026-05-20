<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RsvpController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — LifeAfter
|--------------------------------------------------------------------------
*/

// ─── Auth: Public (tanpa autentikasi) ─────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login',    [AuthController::class, 'login'])->name('login');
});

// ─── Protected Routes (butuh token Sanctum + akun aktif) ──────────────────
Route::middleware(['auth:sanctum', 'check.status'])->group(function () {

    // Auth
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me',      [AuthController::class, 'me'])->name('me');
    });

    // Users — PERHATIAN: /users/map HARUS sebelum /users/{id}
    Route::prefix('users')->name('api.users.')->group(function () {
        Route::get('/',            [UserController::class, 'index'])->name('index');
        Route::get('/map',         [UserController::class, 'map'])->name('map');
        Route::get('/{id}',        [UserController::class, 'show'])->name('show');
        Route::put('/{id}',        [UserController::class, 'update'])->name('update');
        Route::post('/{id}/photo', [UserController::class, 'uploadPhoto'])->name('uploadPhoto');
    });

    // ─── Posts ────────────────────────────────────────────────────────────
    Route::prefix('posts')->name('api.posts.')->group(function () {
        Route::get('/',            [PostController::class, 'index'])->name('index');
        Route::post('/',           [PostController::class, 'store'])->name('store');
        Route::get('/{id}',        [PostController::class, 'show'])->name('show');
        Route::put('/{id}',        [PostController::class, 'update'])->name('update');
        Route::delete('/{id}',     [PostController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/like',  [LikeController::class, 'togglePost'])->name('like');
        // Comments on post — nested under /posts/{id}/comments
        Route::get('/{id}/comments',  [CommentController::class, 'indexForPost'])->name('comments.index');
        Route::post('/{id}/comments', [CommentController::class, 'storeForPost'])->name('comments.store');
    });

    // ─── Gallery ──────────────────────────────────────────────────────
    Route::prefix('gallery')->name('api.gallery.')->group(function () {
        Route::get('/',           [GalleryController::class, 'index'])->name('index');
        Route::post('/',          [GalleryController::class, 'store'])->name('store');
        // PERHATIAN: route statis /{id}/tag harus sebelum /{id} agar tidak konflik
        Route::get('/{id}',       [GalleryController::class, 'show'])->name('show');
        Route::post('/{id}/tag',  [GalleryController::class, 'tagUsers'])->name('tag');
        Route::delete('/{id}',    [GalleryController::class, 'destroy'])->name('destroy');
        // Comments on gallery photo
        Route::get('/{id}/comments',  [CommentController::class, 'indexForGallery'])->name('comments.index');
        Route::post('/{id}/comments', [CommentController::class, 'storeForGallery'])->name('comments.store');
    });

    // ─── Comments (standalone delete) ────────────────────────────────────
    Route::prefix('comments')->name('api.comments.')->group(function () {
        Route::delete('/{id}', [CommentController::class, 'destroy'])->name('destroy');
    });

    // ─── Events ─────────────────────────────────────────────────────
    Route::prefix('events')->name('api.events.')->group(function () {
        Route::get('/',                [EventController::class, 'index'])->name('index');
        Route::get('/{id}',            [EventController::class, 'show'])->name('show');
        Route::get('/{id}/attendees',  [EventController::class, 'attendees'])->name('attendees');
        Route::post('/{id}/rsvp',      [RsvpController::class, 'toggle'])->name('rsvp');
    });

    // ─── Notifications ───────────────────────────────────────────────
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/',              [NotificationController::class, 'index'])->name('index');
        // ⚠️ read-all HARUS sebelum {id}/read untuk menghindari konflik routing
        Route::put('/read-all',      [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::put('/{id}/read',     [NotificationController::class, 'markAsRead'])->name('read');
    });

    // ─── Admin Routes (tambahan: check.admin) ───────────────────────────
    Route::middleware('check.admin')->prefix('admin')->name('api.admin.')->group(function () {
        // Stats (milik Rafif)
        Route::get('/stats',                          [StatsController::class,  'index'])->name('stats');

        // Events (admin only)
        Route::post('/events',                        [EventController::class,  'store'])->name('events.store');
        Route::put('/events/{id}',                    [EventController::class,  'update'])->name('events.update');

        // User management
        Route::get('/pending',                        [AdminController::class,  'pendingUsers'])->name('pending');
        Route::post('/users/{id}/approve',            [AdminController::class,  'approveUser'])->name('users.approve');
        Route::post('/users/{id}/reject',             [AdminController::class,  'rejectUser'])->name('users.reject');
        Route::delete('/users/{id}',                  [AdminController::class,  'deleteUser'])->name('users.delete');

        // Broadcast notification
        Route::post('/notifications/broadcast',       [AdminController::class,  'broadcastNotification'])->name('notifications.broadcast');
    });

});
