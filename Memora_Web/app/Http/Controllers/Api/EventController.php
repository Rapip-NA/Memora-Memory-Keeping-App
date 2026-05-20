<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventMinimalResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a paginated list of events with optional upcoming/past filter.
     */
    public function index(Request $request)
    {
        $query = Event::with('creator')->orderBy('event_date', 'desc');

        if ($request->boolean('upcoming')) {
            $query->where('event_date', '>=', now());
        } elseif ($request->boolean('past')) {
            $query->where('event_date', '<', now());
        }

        $events = $query->paginate(10);

        return EventMinimalResource::collection($events)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly created event (admin only — enforced at route level).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'event_date'  => 'required|date|after:now',
            'location'    => 'nullable|string|max:255',
        ], [
            'title.required'       => 'Judul event tidak boleh kosong.',
            'title.max'            => 'Judul event tidak boleh lebih dari 255 karakter.',
            'description.required' => 'Deskripsi event tidak boleh kosong.',
            'event_date.required'  => 'Tanggal event tidak boleh kosong.',
            'event_date.date'      => 'Format tanggal event tidak valid.',
            'event_date.after'     => 'Tanggal event harus setelah sekarang.',
            'location.max'         => 'Lokasi tidak boleh lebih dari 255 karakter.',
        ]);

        $event = Event::create([
            'title'       => $request->title,
            'description' => $request->description,
            'event_date'  => $request->event_date,
            'location'    => $request->location,
            'created_by'  => auth()->id(),
        ]);

        $event->load('creator');

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified event.
     */
    public function show(Request $request, $id)
    {
        $event = Event::with('creator')->find($id);

        if (! $event) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Event tidak ditemukan.',
            ], 404);
        }

        return new EventResource($event);
    }

    /**
     * Update the specified event (admin only — enforced at route level).
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (! $event) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Event tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'event_date'  => 'sometimes|date',
            'location'    => 'sometimes|nullable|string|max:255',
        ], [
            'title.max'        => 'Judul event tidak boleh lebih dari 255 karakter.',
            'event_date.date'  => 'Format tanggal event tidak valid.',
            'location.max'     => 'Lokasi tidak boleh lebih dari 255 karakter.',
        ]);

        $event->update($request->only(['title', 'description', 'event_date', 'location']));
        $event->load('creator');

        return new EventResource($event);
    }

    /**
     * Return users who RSVP'd as 'hadir' for the event.
     */
    public function attendees(Request $request, $id)
    {
        $event = Event::find($id);

        if (! $event) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Event tidak ditemukan.',
            ], 404);
        }

        $attendees = EventRsvp::where('event_id', $id)
            ->where('status', 'hadir')
            ->with('user')
            ->get()
            ->map(fn ($rsvp) => [
                'id'        => $rsvp->user->id,
                'name'      => $rsvp->user->name,
                'nickname'  => $rsvp->user->nickname,
                'photo_url' => $rsvp->user->photo ? Storage::url($rsvp->user->photo) : null,
                'city'      => $rsvp->user->city,
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $attendees,
        ]);
    }
}
