<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryPhotoMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $caption = $this->caption;

        return [
            'id'             => $this->id,
            'photo_url'      => Storage::url($this->file_path),
            'caption'        => $caption ? (mb_strlen($caption) > 50 ? mb_substr($caption, 0, 50) . '...' : $caption) : null,
            'uploader'       => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'photo_url' => $this->user->photo ? Storage::url($this->user->photo) : null,
            ],
            'comments_count' => $this->comments()->count(),
            'created_at'     => $this->created_at->format('d M Y'),
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
