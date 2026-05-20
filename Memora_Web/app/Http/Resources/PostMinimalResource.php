<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $content = $this->content;

        return [
            'id'             => $this->id,
            'content'        => mb_strlen($content) > 150 ? mb_substr($content, 0, 150) . '...' : $content,
            'photo_url'      => $this->photo ? Storage::url($this->photo) : null,
            'category'       => $this->category,
            'likes_count'    => $this->likes_count,
            'is_liked'       => $this->likes()->where('user_id', auth()->id())->exists(),
            'comments_count' => $this->comments()->count(),
            'author'         => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'photo_url' => $this->user->photo ? Storage::url($this->user->photo) : null,
            ],
            'created_at'     => $this->created_at->format('d M Y, H:i'),
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
