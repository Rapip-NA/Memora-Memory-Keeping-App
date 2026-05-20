<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /** Pembuat event */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get a cleaned up description string (removes standard event template markers).
     */
    public function getCleanDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }
        $cleanDesc = $this->description;
        $cleanDesc = preg_replace('/🎉 Acara:.*?\n/i', '', $cleanDesc);
        $cleanDesc = preg_replace('/📅 Tanggal:.*?\n/i', '', $cleanDesc);
        $cleanDesc = preg_replace('/📍 Lokasi:.*?$/i', '', $cleanDesc);
        return trim($cleanDesc);
    }
}
