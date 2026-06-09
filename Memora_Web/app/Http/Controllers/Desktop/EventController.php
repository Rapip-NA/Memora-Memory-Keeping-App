<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $upcomingEvents = Event::with('rsvps')->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->get();
        $pastEvents = Event::with('rsvps')->whereDate('event_date', '<', now()->toDateString())
            ->orderBy('event_date', 'desc')
            ->get();
        
        return view('desktop.events.index', compact('currentUser', 'upcomingEvents', 'pastEvents'));
    }

    public function show($id)
    {
        $event = Event::with(['rsvps.user', 'comments.user'])->findOrFail($id);
        $currentUser = auth()->user();
        
        return view('desktop.events.show', compact('event', 'currentUser'));
    }

    public function rsvp($id)
    {
        $event = Event::findOrFail($id);
        $user = auth()->user();
        
        $existing = $event->rsvps()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            return back()->with('success', 'RSVP dibatalkan.');
        } else {
            $event->rsvps()->create(['user_id' => $user->id, 'status' => 'hadir']);
            return back()->with('success', 'Berhasil RSVP!');
        }
    }

    public function comment(Request $request, $id)
    {
        $request->validate(['body' => 'required|string']);
        $event = Event::findOrFail($id);
        $user = auth()->user();
        
        $event->comments()->create([
            'user_id' => $user->id,
            'body'    => $request->body,
        ]);
        
        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
