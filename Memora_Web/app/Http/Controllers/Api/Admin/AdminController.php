<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * List all users with status 'pending', ordered oldest first.
     */
    public function pendingUsers(Request $request)
    {
        $users = User::where('status', 'pending')
            ->oldest()
            ->get()
            ->map(fn ($user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'city'       => $user->city,
                'job'        => $user->job,
                'created_at' => $user->created_at->format('d M Y, H:i'),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ]);
    }

    /**
     * Approve a pending user and send them an approval notification.
     */
    public function approveUser(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ($user->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak dalam status pending.',
            ], 422);
        }

        $user->update(['status' => 'active']);

        Notification::create([
            'user_id' => $user->id,
            'type'    => 'approval',
            'data'    => ['message' => 'Akun kamu telah disetujui! Selamat bergabung di Lifeafter.'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Anggota berhasil disetujui.',
            'data'    => [
                'user' => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'status' => $user->status,
                ],
            ],
        ]);
    }

    /**
     * Reject a pending registration by setting the user status to 'inactive'.
     */
    public function rejectUser(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $user->update(['status' => 'inactive']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pendaftaran ditolak.',
        ]);
    }

    /**
     * Soft delete a member account (cannot delete other admins or self).
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak dapat menghapus akun sendiri.',
            ], 403);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak dapat menghapus akun admin lain.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Akun berhasil dihapus.',
        ]);
    }

    /**
     * Broadcast a notification to all active users via efficient batch insert.
     */
    public function broadcastNotification(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'title'   => 'nullable|string|max:100',
        ], [
            'message.required' => 'Isi pesan tidak boleh kosong.',
            'message.max'      => 'Pesan tidak boleh lebih dari 500 karakter.',
            'title.max'        => 'Judul tidak boleh lebih dari 100 karakter.',
        ]);

        $users = User::where('status', 'active')->get();
        $count = $users->count();
        $now   = now();

        Notification::insert(
            $users->map(fn ($user) => [
                'user_id'    => $user->id,
                'type'       => 'broadcast',
                'data'       => json_encode([
                    'title'   => $request->title ?? 'Pengumuman',
                    'message' => $request->message,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray()
        );

        return response()->json([
            'status'  => 'success',
            'message' => "Notifikasi berhasil dikirim ke {$count} anggota.",
            'data'    => [
                'sent_to' => $count,
            ],
        ]);
    }
}
