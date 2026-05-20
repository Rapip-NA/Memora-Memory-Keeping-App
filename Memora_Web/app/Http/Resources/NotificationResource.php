<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type'       => $this->type,
            'title'      => $this->getTitle(),
            'data'       => $this->data,
            'is_read'    => ! is_null($this->read_at),
            'created_at' => $this->created_at->format('d M Y, H:i'),
        ];
    }

    /**
     * Resolve a human-readable title based on notification type.
     */
    protected function getTitle(): string
    {
        return match ($this->type) {
            'birthday'  => '🎂 Ulang Tahun Teman',
            'new_event' => '📅 Event Baru',
            'new_post'  => '📝 Post Baru',
            'broadcast' => '📢 Pengumuman',
            'approval'  => '✅ Akun Disetujui',
            default     => 'Notifikasi',
        };
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
