<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CommentResource extends JsonResource
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
            'body'       => $this->body,
            'author'     => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'nickname'  => $this->user->nickname,
                'photo_url' => $this->user->photo ? Storage::url($this->user->photo) : null,
            ],
            'created_at' => $this->created_at->format('d M Y, H:i'),
            'is_own'     => $this->user_id === auth()->id(),
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
