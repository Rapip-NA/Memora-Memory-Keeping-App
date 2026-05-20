<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Versi ringan untuk list direktori & peta anggota.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'nickname' => $this->nickname,
            'city'     => $this->city,
            'job'      => $this->job,
            'company'  => $this->company,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'lat'      => $this->lat,
            'lng'      => $this->lng,
        ];
    }
}
