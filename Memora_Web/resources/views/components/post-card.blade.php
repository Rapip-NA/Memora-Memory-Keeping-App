@props(['post', 'currentUser' => null])

<div class="post-card">
    <div class="post-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div class="poster-info">
            @php 
                $avatar = $post->user->avatar_url;
            @endphp
            <img src="{{ $avatar }}" alt="{{ $post->user->name ?? 'User' }}" class="avatar" style="object-fit: cover;">
            <div>
                <h4>{{ $post->user->name ?? 'User' }}</h4>
                <p>{{ $post->category ?? 'Update' }} • {{ $post->created_at->diffForHumans() }}</p>
            </div>
        </div>
        
        @if(isset($currentUser) && $post->user_id === $currentUser->id && request()->routeIs('desktop.profile'))
        <div class="post-management" style="display: flex; gap: 8px;">
            <button onclick="editPost({{ $post->id }})" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class='bx bx-edit' style="font-size: 20px;"></i></button>
            <form action="{{ route('desktop.post.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus post ini?');" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class='bx bx-trash' style="font-size: 20px;"></i></button>
            </form>
        </div>
        @endif
    </div>

    <!-- Display Mode -->
    <div class="post-content" id="post-content-{{ $post->id }}">
        @php
            $eventName = null;
            $eventDateStr = null;
            $eventLocation = null;
            $isEvent = false;
            
            $cleanContent = $post->content;
            if (preg_match('/🎉 Acara: (.*)/', $post->content, $matches)) {
                $eventName = trim($matches[1]);
                $isEvent = true;
                $cleanContent = preg_replace('/🎉 Acara: .*/', '', $cleanContent);
            }
            if (preg_match('/📅 Tanggal: (.*)/', $post->content, $matches)) {
                $eventDateStr = trim($matches[1]);
                $cleanContent = preg_replace('/📅 Tanggal: .*/', '', $cleanContent);
            }
            if (preg_match('/📍 Lokasi: (.*)/', $post->content, $matches)) {
                $eventLocation = trim($matches[1]);
                $cleanContent = preg_replace('/📍 Lokasi: .*/', '', $cleanContent);
            }
            $cleanContent = trim($cleanContent);
            
            $eventMonth = 'TBA';
            $eventDay = '--';
            $eventTime = '';
            if ($eventDateStr) {
                if (preg_match('/^(\d+)\s+([A-Za-z]+)\s+\d+(?:\s+Jam\s+(.*))?/', $eventDateStr, $dm)) {
                    $eventDay = $dm[1];
                    $eventMonth = substr($dm[2], 0, 3);
                    $eventTime = $dm[3] ?? '';
                }
            }
        @endphp

        <p>{!! nl2br(e($cleanContent)) !!}</p>
        
        @if($isEvent)
        <!-- Enhanced Event Card UI -->
        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; margin-top: 16px; display: flex; gap: 16px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <!-- Left: Calendar Box -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; min-width: 64px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="background: #ef4444; color: white; text-align: center; font-size: 11px; font-weight: 700; padding: 4px; text-transform: uppercase; letter-spacing: 1px;">
                    {{ strtoupper($eventMonth) }}
                </div>
                <div style="text-align: center; padding: 8px 4px; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;">
                    {{ $eventDay }}
                </div>
            </div>
            
            <!-- Right: Details -->
            <div style="flex: 1; min-width: 0;">
                <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $eventName }}</h4>
                @if($eventTime)
                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">
                    <i class='bx bx-time-five' style="font-size: 16px;"></i> {{ $eventTime }} WIB
                </div>
                @endif
                @if($eventLocation)
                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <i class='bx bx-map' style="font-size: 16px;"></i> {{ $eventLocation }}
                </div>
                @endif
            </div>
        </div>

        @if($eventLocation)
        <!-- Full-width Map Action Button -->
        <a href="https://maps.google.com/maps?q={{ urlencode($eventLocation) }}" target="_blank" style="display: block; width: 100%; text-align: center; background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-dark); font-weight: 600; padding: 12px; border-radius: 12px; margin-top: 12px; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--bg-main)'">
            <i class='bx bx-map-alt' style="margin-right: 6px;"></i> Buka di Google Maps
        </a>

        <!-- Map Iframe -->
        <div class="post-map" style="margin-top: 12px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <iframe 
                width="100%" 
                height="200" 
                style="border:0; display: block;" 
                loading="lazy" 
                allowfullscreen 
                src="https://maps.google.com/maps?q={{ urlencode($eventLocation) }}&t=&z=14&ie=UTF8&iwloc=&output=embed">
            </iframe>
        </div>
        @endif
        @endif

        @if($post->poll)
        @php
            $hasVoted = $currentUser ? $post->poll->hasVoted($currentUser) : false;
            $isExpired = $post->poll->is_expired;
            $showResults = $hasVoted || $isExpired;
            $totalVotes = $post->poll->total_votes;
        @endphp
        <div class="post-poll" id="poll-container-{{ $post->poll->id }}" style="margin-top: 16px; border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; background: var(--bg-card);">
            @foreach($post->poll->options as $option)
                @php
                    $percent = $totalVotes > 0 ? round(($option->votes_count / $totalVotes) * 100) : 0;
                @endphp
                
                @if($showResults)
                    <div style="margin-bottom: 12px; position: relative;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; position: relative; z-index: 2; padding: 0 8px;">
                            <span style="font-weight: 600; font-size: 14px; color: var(--text-dark);">{{ $option->text }}</span>
                            <span style="font-weight: 700; font-size: 14px; color: var(--text-dark);">{{ $percent }}%</span>
                        </div>
                        <div style="height: 36px; background: var(--bg-main); border-radius: 8px; overflow: hidden; position: relative; border: 1px solid var(--border-color);">
                            <div style="height: 100%; width: {{ $percent }}%; background: rgba(29, 155, 240, 0.2); transition: width 0.5s ease-in-out;"></div>
                        </div>
                    </div>
                @else
                    <button class="poll-option-btn" onclick="votePoll({{ $post->poll->id }}, {{ $option->id }})" style="display: block; width: 100%; padding: 10px; margin-bottom: 8px; background: transparent; border: 1px solid #1d9bf0; color: #1d9bf0; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; text-align: center;" onmouseover="this.style.background='rgba(29,155,240,0.1)'" onmouseout="this.style.background='transparent'">
                        {{ $option->text }}
                    </button>
                @endif
            @endforeach
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                <span id="poll-votes-{{ $post->poll->id }}">{{ $totalVotes }} suara</span>
                <span>{{ $isExpired ? 'Polling berakhir' : 'Sisa waktu: ' . $post->poll->expires_at->diffForHumans() }}</span>
            </div>
        </div>
        @endif

        @if($post->photo && $post->photo !== '[]')
            @php
                $photos = json_decode($post->photo, true);
            @endphp
            @if(is_array($photos) && count($photos) > 0)
                <div class="post-media-container" style="position: relative; margin-top: 12px; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden;">
                    <div class="post-photo-slider" id="slider-{{ $post->id }}" onscroll="updateSliderDots({{ $post->id }})" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 0; scrollbar-width: none;">
                        @foreach($photos as $photoUrl)
                            @php
                                $isVideo = preg_match('/\.(mp4|mov|avi|webm|mkv|3gp)$/i', $photoUrl);
                                if (str_starts_with($photoUrl, 'http')) {
                                    $mediaUrl = $photoUrl;
                                } else {
                                    // Prioritaskan lokal (public disk) untuk kecepatan "Instant UI"
                                    // Jika file tidak ada di lokal (misal sudah dibersihkan), baru fallback ke Cloud (R2/S3)
                                    if (file_exists(public_path('storage/' . $photoUrl))) {
                                        $mediaUrl = asset('storage/' . $photoUrl);
                                    } else {
                                        $mediaUrl = Storage::disk('s3')->url($photoUrl);
                                    }
                                }
                            @endphp
                            <div class="post-photo-wrapper" style="flex: 0 0 100%; scroll-snap-align: start; position: relative; {{ $isVideo ? '' : 'cursor: pointer;' }}" {!! $isVideo ? '' : 'onclick="handlePhotoClick(event, ' . $post->id . ', this)" data-gallery="post-' . $post->id . '" data-src="' . $mediaUrl . '"' !!}>
                                @if($isVideo)
                                    <video preload="metadata" src="{{ $mediaUrl }}" controls style="width: 100%; height: auto; max-height: 800px; object-fit: cover; display: block;"></video>
                                @else
                                    <img src="{{ $mediaUrl }}" alt="Post Photo" loading="lazy" style="width: 100%; height: auto; max-height: 800px; object-fit: cover; display: block; pointer-events: none;">
                                    <!-- Heart animation overlay -->
                                    <i class='bx bxs-heart heart-animation' style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); font-size: 80px; color: rgba(255, 255, 255, 0.9); opacity: 0; pointer-events: none; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s; z-index: 10; text-shadow: 0 4px 12px rgba(0,0,0,0.3);"></i>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    
                    @if(count($photos) > 1)
                    <!-- Left Arrow -->
                    <button class="slider-btn prev" onclick="scrollSlider({{ $post->id }}, -1)" style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.6); color: white; border: none; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 10; backdrop-filter: blur(4px); box-shadow: 0 2px 8px rgba(0,0,0,0.2);"><i class='bx bx-chevron-left' style="font-size: 24px;"></i></button>
                    
                    <!-- Right Arrow -->
                    <button class="slider-btn next" onclick="scrollSlider({{ $post->id }}, 1)" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.6); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; backdrop-filter: blur(4px); box-shadow: 0 2px 8px rgba(0,0,0,0.2);"><i class='bx bx-chevron-right' style="font-size: 24px;"></i></button>
                    
                    <!-- Dots -->
                    <div class="slider-dots" id="dots-{{ $post->id }}" style="position: absolute; bottom: 16px; left: 0; right: 0; display: flex; justify-content: center; gap: 6px; z-index: 10;">
                        @foreach($photos as $index => $photoUrl)
                            <div class="dot" style="width: 6px; height: 6px; border-radius: 50%; background: {{ $index === 0 ? '#fff' : 'rgba(255,255,255,0.4)' }}; transition: all 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.5);"></div>
                        @endforeach
                    </div>
                    @endif
                </div>
                <style>
                    .post-photo-slider::-webkit-scrollbar { display: none; }
                </style>
            @else
                @php
                    $isVideoSingle = preg_match('/\.(mp4|mov|avi|webm|mkv|3gp)$/i', $post->photo);
                    if (str_starts_with($post->photo, 'http')) {
                        $singleMediaUrl = $post->photo;
                    } else {
                        if (file_exists(public_path('storage/' . $post->photo))) {
                            $singleMediaUrl = asset('storage/' . $post->photo);
                        } else {
                            $singleMediaUrl = Storage::disk('s3')->url($post->photo);
                        }
                    }
                @endphp
                <div class="post-image-grid mosaic post-photo-wrapper" style="margin-top: 12px; position: relative; {{ $isVideoSingle ? '' : 'cursor: pointer;' }}" {!! $isVideoSingle ? '' : 'onclick="handlePhotoClick(event, ' . $post->id . ', this)" data-gallery="post-' . $post->id . '" data-src="' . $singleMediaUrl . '"' !!}>
                    @if($isVideoSingle)
                        <video preload="metadata" src="{{ $singleMediaUrl }}" controls style="border-radius: 12px; width: 100%; height: auto; max-height: 800px; object-fit: cover; border: 1px solid var(--border-color); display: block;"></video>
                    @else
                        <img src="{{ $singleMediaUrl }}" alt="Post Photo" loading="lazy" style="border-radius: 12px; width: 100%; height: auto; max-height: 800px; object-fit: cover; border: 1px solid var(--border-color); display: block; pointer-events: none;">
                        <i class='bx bxs-heart heart-animation' style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); font-size: 80px; color: rgba(255, 255, 255, 0.9); opacity: 0; pointer-events: none; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s; z-index: 10; text-shadow: 0 4px 12px rgba(0,0,0,0.3);"></i>
                    @endif
                </div>
            @endif
        @endif
    </div>

    @if(isset($currentUser) && $post->user_id === $currentUser->id && request()->routeIs('desktop.profile'))
    <!-- Edit Form Mode (Hidden initially) -->
    <div class="post-edit-form" id="post-edit-{{ $post->id }}" style="display: none; margin-top: 12px;">
        <form action="{{ route('desktop.post.update', $post->id) }}" method="POST">
            @csrf
            @method('PUT')
            <textarea name="content" rows="4" style="width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; background: var(--bg-main); outline: none; margin-bottom: 8px; font-family: inherit;">{{ $post->content }}</textarea>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="cancelEdit({{ $post->id }})" class="btn-solid" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 6px 12px; font-size: 13px;">Batal</button>
                <button type="submit" class="btn-solid" style="padding: 6px 12px; font-size: 13px;">Simpan</button>
            </div>
        </form>
    </div>
    @endif

    <div class="post-actions" style="margin-top: 16px;">
        @php 
            $isLiked = isset($currentUser) && $post->likes->where('user_id', $currentUser->id)->count() > 0; 
        @endphp
        <button class="action-btn {{ $isLiked ? 'active' : '' }}" id="like-btn-{{ $post->id }}" onclick="toggleLike({{ $post->id }})">
            <i class='bx {{ $isLiked ? 'bxs-heart' : 'bx-heart' }}' id="like-icon-{{ $post->id }}" style="{{ $isLiked ? 'color: var(--danger);' : '' }}"></i> 
            <span id="like-count-{{ $post->id }}">{{ $post->likes_count ?? 0 }}</span>
        </button>
        <button class="action-btn" onclick="toggleComments({{ $post->id }})">
            <i class='bx bx-comment'></i> {{ $post->comments->count() }}
        </button>
        <button class="action-btn" onclick="sharePost({{ $post->id }})"><i class='bx bx-share'></i> Share</button>
        
        @php 
            $isBookmarked = isset($currentUser) && $post->bookmarks->where('user_id', $currentUser->id)->count() > 0; 
        @endphp
        <button class="action-btn ms-auto {{ $isBookmarked ? 'active' : '' }}" id="bookmark-btn-{{ $post->id }}" onclick="toggleBookmark({{ $post->id }})">
            <i class='bx {{ $isBookmarked ? 'bxs-bookmark' : 'bx-bookmark' }}' id="bookmark-icon-{{ $post->id }}" style="{{ $isBookmarked ? 'color: var(--primary);' : '' }}"></i>
        </button>
    </div>

    <!-- Comments Section -->
    <div class="comments-section" id="comments-{{ $post->id }}" style="display: none; margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <!-- Existing Comments -->
        <div class="existing-comments" style="max-height: 200px; overflow-y: auto; margin-bottom: 12px;">
            @foreach($post->comments as $comment)
            <div class="comment-item" style="display: flex; gap: 12px; margin-bottom: 12px;">
                @php 
                    $cAvatar = $comment->user->photo 
                        ? Storage::url($comment->user->photo) 
                        : "https://ui-avatars.com/api/?name=".urlencode($comment->user->name ?? 'User')."&background=random&color=fff&size=32";
                @endphp
                <img src="{{ $cAvatar }}" alt="Avatar" class="avatar" style="width: 32px; height: 32px; object-fit: cover;">
                <div style="background: var(--bg-main); padding: 8px 12px; border-radius: 12px; flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <h6 style="font-size: 13px; margin: 0;">{{ $comment->user->name ?? 'User' }}</h6>
                        <span style="font-size: 11px; color: var(--text-muted);">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p style="font-size: 13px; margin: 4px 0 0 0;">{{ $comment->body }}</p>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Comment Form -->
        <form action="{{ route('desktop.post.comment', $post->id) }}" method="POST" style="display: flex; gap: 12px; align-items: center;">
            @csrf
            @php 
                $uAvatar = isset($currentUser) && $currentUser->photo 
                    ? Storage::url($currentUser->photo) 
                    : "https://ui-avatars.com/api/?name=".urlencode($currentUser->name ?? 'User')."&background=000000&color=fff&size=32";
            @endphp
            <img src="{{ $uAvatar }}" class="avatar" style="width: 32px; height: 32px; object-fit: cover;">
            <input type="text" name="body" placeholder="Tulis komentar..." style="flex: 1; border: none; background: var(--bg-main); padding: 8px 16px; border-radius: var(--radius-full); outline: none; font-size: 13px; color: var(--text-dark);" required>
            <button type="submit" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class='bx bxs-send' style="font-size: 20px;"></i></button>
        </form>
    </div>
</div>
