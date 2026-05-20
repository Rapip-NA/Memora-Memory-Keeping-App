<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryPhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $taggedUsers = User::whereIn('id', $this->tagged_user_ids ?? [])
            ->get()
            ->map(fn ($user) => [
                'id'        => $user->id,
                'name'      => $user->name,
                'nickname'  => $user->nickname,
                'photo_url' => $user->photo ? Storage::url($user->photo) : null,
            ]);

        return [
            'id'             => $this->id,
            'photo_url'      => Storage::url($this->file_path),
            'caption'        => $this->caption,
            'uploader'       => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'nickname'  => $this->user->nickname,
                'photo_url' => $this->user->photo ? Storage::url($this->user->photo) : null,
            ],
            'tagged_users'   => $taggedUsers,
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
