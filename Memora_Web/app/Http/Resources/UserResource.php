<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'nickname'   => $this->nickname,
            'email'      => $this->email,
            'role'       => $this->role,
            'status'     => $this->status,
            'city'       => $this->city,
            'job'        => $this->job,
            'company'    => $this->company,
            'bio'        => $this->bio,
            'quote'      => $this->quote,
            'born_date'  => $this->born_date?->format('Y-m-d'),
            'lat'        => $this->lat,
            'lng'        => $this->lng,
            'photo_url'  => $this->photo ? Storage::url($this->photo) : null,
            'social_links' => $this->social_links,
            'created_at' => $this->created_at?->format('d M Y'),
        ];
    }
}
