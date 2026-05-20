<?php

namespace App\Http\Resources;

use App\Models\EventRsvp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'location'        => $this->location,
            'event_date'      => $this->event_date->format('d M Y, H:i'),
            'event_date_raw'  => $this->event_date->toISOString(),
            'is_past'         => $this->event_date->isPast(),
            'organizer'       => [
                'id'        => $this->creator->id,
                'name'      => $this->creator->name,
                'photo_url' => $this->creator->photo ? Storage::url($this->creator->photo) : null,
            ],
            'attendees_count' => EventRsvp::where('event_id', $this->id)
                ->where('status', 'hadir')
                ->count(),
            'my_rsvp'         => EventRsvp::where('event_id', $this->id)
                ->where('user_id', auth()->id())
                ->value('status'),
            'created_at'      => $this->created_at->format('d M Y'),
        ];
    }

    /**
     * Wrap the response with status: success.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'status' => 'success',
        ];
    }
}
