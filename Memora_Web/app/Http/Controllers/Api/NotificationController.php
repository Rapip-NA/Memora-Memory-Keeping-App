<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a paginated list of notifications for the authenticated user.
     * Optionally filter to unread only with ?unread=1.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = Notification::where('user_id', $userId)->latest();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $paginated   = $query->paginate(20);
        $unreadCount = Notification::where('user_id', $userId)->whereNull('read_at')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'notifications' => NotificationResource::collection($paginated)->resolve(),
                'unread_count'  => $unreadCount,
            ],
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::find($id);

        if (! $notification) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak memiliki akses ke notifikasi ini.',
            ], 403);
        }

        // Hanya update jika belum dibaca
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return new NotificationResource($notification);
    }

    /**
     * Mark all unread notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request)
    {
        $updatedCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'data'    => [
                'updated_count' => $updatedCount,
            ],
        ]);
    }
}
