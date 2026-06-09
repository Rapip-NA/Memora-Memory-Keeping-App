<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'classroom_id',
        'name',
        'nickname',
        'email',
        'password',
        'role',
        'status',
        'city',
        'job',
        'company',
        'bio',
        'quote',
        'born_date',
        'lat',
        'lng',
        'photo',
        'banner_photo',
        'social_links',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'born_date'         => 'date',
            'social_links'      => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function eventRsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    /** User sebagai liker */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Get synchronized avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if (!$this->photo) {
            return "https://ui-avatars.com/api/?name=" . urlencode($this->name ?? 'User') . "&background=000000&color=fff&size=100";
        }

        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        // Prioritas lokal untuk sinkronisasi instan
        if (file_exists(public_path('storage/' . $this->photo))) {
            return asset('storage/' . $this->photo);
        }

        // Fallback ke Cloud (R2/S3) jika default filesystem adalah s3 atau file tidak di lokal
        try {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->photo);
        } catch (\Exception $e) {
            return asset('storage/' . $this->photo);
        }
    }

    /**
     * Get synchronized banner URL
     */
    public function getBannerUrlAttribute(): string
    {
        if (!$this->banner_photo) {
            return "https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2000";
        }

        if (str_starts_with($this->banner_photo, 'http')) {
            return $this->banner_photo;
        }

        if (file_exists(public_path('storage/' . $this->banner_photo))) {
            return asset('storage/' . $this->banner_photo);
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->banner_photo);
        } catch (\Exception $e) {
            return asset('storage/' . $this->banner_photo);
        }
    }
}
