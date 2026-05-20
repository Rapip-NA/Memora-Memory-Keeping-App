<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    /**
     * Toggle (or update) RSVP status for the authenticated user on an event.
     */
    public function toggle(Request $request, $eventId)
    {
        $event = Event::find($eventId);

        if (! $event) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Event tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:hadir,tidak_hadir',
        ], [
            'status.required' => 'Status RSVP tidak boleh kosong.',
            'status.in'       => 'Status RSVP tidak valid. Pilih: hadir atau tidak_hadir.',
        ]);

        EventRsvp::updateOrCreate(
            [
                'event_id' => $eventId,
                'user_id'  => auth()->id(),
            ],
            [
                'status' => $request->status,
            ]
        );

        $attendeesCount = EventRsvp::where('event_id', $eventId)
            ->where('status', 'hadir')
            ->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'rsvp_status'     => $request->status,
                'attendees_count' => $attendeesCount,
            ],
        ]);
    }
}
