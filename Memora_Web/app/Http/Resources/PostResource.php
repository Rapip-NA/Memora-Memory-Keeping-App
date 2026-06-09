<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Resolve photo path: handles both plain string (mobile) and JSON array (web).
     */
    protected function resolvePhotoUrl(?string $photo): ?string
    {
        if (!$photo) return null;

        // Web uploads store an array of paths as JSON
        $decoded = json_decode($photo, true);
        if (is_array($decoded) && count($decoded) > 0) {
            return Storage::url($decoded[0]);
        }

        // Mobile uploads store a plain path string
        return Storage::url($photo);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'content'        => $this->content,
            'photo_url'      => $this->resolvePhotoUrl($this->photo),
            'category'       => $this->category,
            'likes_count'    => $this->likes_count,
            'is_liked'       => $this->likes()->where('user_id', auth()->id())->exists(),
            'is_bookmarked'  => $this->bookmarks()->where('user_id', auth()->id())->exists(),
            'comments_count' => $this->comments()->count(),
            'author'         => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'nickname'  => $this->user->nickname,
                'photo_url' => $this->resolvePhotoUrl($this->user->photo),
                'city'      => $this->user->city,
                'job'       => $this->user->job,
            ],
            'created_at'     => $this->created_at->format('d M Y, H:i'),
            'poll'           => (function () {
                $poll = $this->relationLoaded('poll') ? $this->poll : null;
                if (!$poll) return null;

                $totalVotes = $poll->votes->count();
                $userId     = auth()->id();
                $userVote   = $poll->votes->firstWhere('user_id', $userId);

                return [
                    'id'          => $poll->id,
                    'expires_at'  => $poll->expires_at?->toISOString(),
                    'is_expired'  => $poll->is_expired,
                    'total_votes' => $totalVotes,
                    'user_voted_option_id' => $userVote?->poll_option_id,
                    'options'     => $poll->options->map(fn ($opt) => [
                        'id'          => $opt->id,
                        'text'        => $opt->text,
                        'votes_count' => $opt->votes_count,
                        'percent'     => $totalVotes > 0
                            ? round(($opt->votes_count / $totalVotes) * 100)
                            : 0,
                    ])->values(),
                ];
            })(),
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
