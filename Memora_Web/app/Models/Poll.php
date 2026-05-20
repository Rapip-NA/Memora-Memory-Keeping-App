<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = [
        'post_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function getTotalVotesAttribute()
    {
        return $this->votes()->count();
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->expires_at) return false;
        return $this->expires_at->isPast();
    }

    public function hasVoted(User $user)
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }
}
