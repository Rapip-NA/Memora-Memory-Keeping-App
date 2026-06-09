@extends('layouts.desktop')

@section('content')

<div class="content-grid" style="grid-template-columns: minmax(0, 680px) 340px; justify-content: center;">
    <!-- CENTER COLUMN: POST DETAIL -->
    <div class="feed-column">

        <!-- Back Navigation -->
        <a href="{{ url()->previous() }}" class="post-show-back-btn" id="back-to-feed">
            <i class='bx bx-arrow-back'></i>
            <span>Kembali</span>
        </a>

        <!-- Post Card -->
        <div class="post-card post-show-card">
            <div class="post-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="poster-info">
                    @php
                        $avatar = $post->user->avatar_url;
                    @endphp
                    <img src="{{ $avatar }}" alt="{{ $post->user->name ?? 'User' }}" class="avatar" style="object-fit: cover;">
                    <div>
                        <h4>{{ $post->user->name ?? 'User' }}</h4>
                        <p>{{ $post->category ?? 'Update' }} • {{ $post->created_at->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                </div>

                @if(isset($currentUser) && $post->user_id === $currentUser->id)
                <div class="post-management" style="display: flex; gap: 8px;">
                    <button onclick="toggleEditMode()" style="background: none; border: none; color: var(--primary); cursor: pointer;" title="Edit">
                        <i class='bx bx-edit' style="font-size: 20px;"></i>
                    </button>
                    <form action="{{ route('desktop.post.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin menghapus postingan ini?');" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer;" title="Hapus">
                            <i class='bx bx-trash' style="font-size: 20px;"></i>
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Post Content -->
            <div class="post-content" id="post-content-display">
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

                <p style="font-size: 15px; line-height: 1.6;">{!! nl2br(e($cleanContent)) !!}</p>

                @if($isEvent)
                <!-- Enhanced Event Card UI -->
                <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; margin-top: 16px; display: flex; gap: 16px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; min-width: 64px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <div style="background: #ef4444; color: white; text-align: center; font-size: 11px; font-weight: 700; padding: 4px; text-transform: uppercase; letter-spacing: 1px;">
                            {{ strtoupper($eventMonth) }}
                        </div>
                        <div style="text-align: center; padding: 8px 4px; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;">
                            {{ $eventDay }}
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: var(--text-dark);">{{ $eventName }}</h4>
                        @if($eventTime)
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">
                            <i class='bx bx-time-five' style="font-size: 16px;"></i> {{ $eventTime }} WIB
                        </div>
                        @endif
                        @if($eventLocation)
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted);">
                            <i class='bx bx-map' style="font-size: 16px;"></i> {{ $eventLocation }}
                        </div>
                        @endif
                    </div>
                </div>

                @if($eventLocation)
                <a href="https://maps.google.com/maps?q={{ urlencode($eventLocation) }}" target="_blank" style="display: block; width: 100%; text-align: center; background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-dark); font-weight: 600; padding: 12px; border-radius: 12px; margin-top: 12px; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--bg-main)'">
                    <i class='bx bx-map-alt' style="margin-right: 6px;"></i> Buka di Google Maps
                </a>
                <div style="margin-top: 12px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
                    <iframe width="100%" height="250" style="border:0; display: block;" loading="lazy" allowfullscreen src="https://maps.google.com/maps?q={{ urlencode($eventLocation) }}&t=&z=14&ie=UTF8&iwloc=&output=embed"></iframe>
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
                        @php $percent = $totalVotes > 0 ? round(($option->votes_count / $totalVotes) * 100) : 0; @endphp
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

                <!-- Media -->
                @if($post->photo && $post->photo !== '[]')
                    @php
                        $photos = json_decode($post->photo, true);
                    @endphp
                    @if(is_array($photos) && count($photos) > 0)
                        <div class="post-media-container" style="position: relative; margin-top: 16px; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden;">
                            <div class="post-photo-slider" id="slider-{{ $post->id }}" onscroll="updateSliderDots({{ $post->id }})" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 0; scrollbar-width: none;">
                                @foreach($photos as $photoUrl)
                                    @php
                                        $isVideo = preg_match('/\.(mp4|mov|avi|webm|mkv|3gp)$/i', $photoUrl);
                                        if (str_starts_with($photoUrl, 'http')) {
                                            $mediaUrl = $photoUrl;
                                        } else {
                                            if (file_exists(public_path('storage/' . $photoUrl))) {
                                                $mediaUrl = asset('storage/' . $photoUrl);
                                            } else {
                                                $mediaUrl = Storage::disk('s3')->url($photoUrl);
                                            }
                                        }
                                    @endphp
                                    <div style="flex: 0 0 100%; scroll-snap-align: start; position: relative;">
                                        @if($isVideo)
                                            <video preload="metadata" src="{{ $mediaUrl }}" controls style="width: 100%; height: auto; max-height: 500px; object-fit: cover; display: block;"></video>
                                        @else
                                            <a href="{{ $mediaUrl }}" data-fancybox="post-gallery" data-caption="Post oleh {{ $post->user->name }}">
                                                <img src="{{ $mediaUrl }}" alt="Post Photo" loading="lazy" style="width: 100%; height: auto; max-height: 500px; object-fit: cover; display: block;">
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if(count($photos) > 1)
                            <button class="slider-btn prev" onclick="scrollSlider({{ $post->id }}, -1)" style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.6); color: white; border: none; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 10; backdrop-filter: blur(4px);">
                                <i class='bx bx-chevron-left' style="font-size: 24px;"></i>
                            </button>
                            <button class="slider-btn next" onclick="scrollSlider({{ $post->id }}, 1)" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.6); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; backdrop-filter: blur(4px);">
                                <i class='bx bx-chevron-right' style="font-size: 24px;"></i>
                            </button>
                            <div class="slider-dots" id="dots-{{ $post->id }}" style="position: absolute; bottom: 16px; left: 0; right: 0; display: flex; justify-content: center; gap: 6px; z-index: 10;">
                                @foreach($photos as $index => $photoUrl)
                                    <div class="dot" style="width: 6px; height: 6px; border-radius: 50%; background: {{ $index === 0 ? '#fff' : 'rgba(255,255,255,0.4)' }}; transition: all 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.5);"></div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <style>.post-photo-slider::-webkit-scrollbar { display: none; }</style>
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
                        <div style="margin-top: 16px;">
                            @if($isVideoSingle)
                                <video preload="metadata" src="{{ $singleMediaUrl }}" controls style="border-radius: 12px; width: 100%; height: auto; max-height: 500px; object-fit: cover; border: 1px solid var(--border-color); display: block;"></video>
                            @else
                                <a href="{{ $singleMediaUrl }}" data-fancybox="post-gallery" data-caption="Post oleh {{ $post->user->name }}">
                                    <img src="{{ $singleMediaUrl }}" alt="Post Photo" loading="lazy" style="border-radius: 12px; width: 100%; height: auto; max-height: 500px; object-fit: cover; border: 1px solid var(--border-color); display: block;">
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <!-- Edit Form (Hidden) -->
            @if(isset($currentUser) && $post->user_id === $currentUser->id)
            <div id="post-edit-form" style="display: none; margin-top: 16px;">
                <form action="{{ route('desktop.post.update', $post->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <textarea name="content" rows="5" style="width: 100%; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; background: var(--bg-main); outline: none; margin-bottom: 12px; font-family: inherit; font-size: 15px; resize: vertical; color: var(--text-dark);">{{ $post->content }}</textarea>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" onclick="toggleEditMode()" style="background: var(--bg-main); color: var(--text-dark); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 999px; font-weight: 600; cursor: pointer; font-size: 14px;">Batal</button>
                        <button type="submit" style="background: var(--primary-color); color: var(--primary-inverse); border: none; padding: 10px 20px; border-radius: 999px; font-weight: 600; cursor: pointer; font-size: 14px;">Simpan</button>
                    </div>
                </form>
            </div>
            @endif

            <!-- Post Actions -->
            <div class="post-actions" style="margin-top: 20px;">
                @php
                    $isLiked = isset($currentUser) && $post->likes->where('user_id', $currentUser->id)->count() > 0;
                @endphp
                <button class="action-btn {{ $isLiked ? 'active' : '' }}" id="like-btn-{{ $post->id }}" onclick="toggleLike({{ $post->id }})">
                    <i class='bx {{ $isLiked ? 'bxs-heart' : 'bx-heart' }}' id="like-icon-{{ $post->id }}" style="{{ $isLiked ? 'color: var(--danger);' : '' }}"></i>
                    <span id="like-count-{{ $post->id }}">{{ $post->likes_count ?? 0 }}</span>
                </button>
                <button class="action-btn" onclick="document.getElementById('comment-input').focus()">
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

            <!-- Post Info -->
            <div class="post-show-meta">
                <span><i class='bx bx-heart'></i> {{ $post->likes_count ?? 0 }} suka</span>
                <span>•</span>
                <span><i class='bx bx-comment'></i> {{ $post->comments->count() }} komentar</span>
                <span>•</span>
                <span><i class='bx bx-time-five'></i> {{ $post->created_at->diffForHumans() }}</span>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="post-card post-show-comments">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">
                <i class='bx bx-conversation' style="margin-right: 6px;"></i>
                Komentar ({{ $post->comments->count() }})
            </h3>

            <!-- Comment Form -->
            <form action="{{ route('desktop.post.comment', $post->id) }}" method="POST" class="post-show-comment-form">
                @csrf
                @php
                    $uAvatar = isset($currentUser) && $currentUser->photo
                        ? Storage::url($currentUser->photo)
                        : "https://ui-avatars.com/api/?name=".urlencode($currentUser->name ?? 'User')."&background=000000&color=fff&size=40";
                @endphp
                <img src="{{ $currentUser->avatar_url ?? $uAvatar }}" class="avatar" style="width: 40px; height: 40px; object-fit: cover;">
                <div style="flex: 1; position: relative;">
                    <input type="text" name="body" id="comment-input" placeholder="Tulis komentar..." required style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-main); padding: 12px 48px 12px 16px; border-radius: 999px; outline: none; font-size: 14px; color: var(--text-dark); font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s;">
                    <button type="submit" style="position: absolute; right: 4px; top: 50%; transform: translateY(-50%); background: var(--primary-color); color: var(--primary-inverse); border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
                        <i class='bx bxs-send' style="font-size: 18px;"></i>
                    </button>
                </div>
            </form>

            <!-- Comment List -->
            <div class="post-show-comment-list">
                @forelse($post->comments->sortByDesc('created_at') as $comment)
                <div class="post-show-comment-item">
                    @php
                        $cAvatar = $comment->user->photo
                            ? Storage::url($comment->user->photo)
                            : "https://ui-avatars.com/api/?name=".urlencode($comment->user->name ?? 'User')."&background=random&color=fff&size=36";
                    @endphp
                    <img src="{{ $comment->user->avatar_url ?? $cAvatar }}" alt="Avatar" class="avatar" style="width: 36px; height: 36px; object-fit: cover; flex-shrink: 0;">
                    <div style="flex: 1; background: var(--bg-main); padding: 12px 16px; border-radius: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <h6 style="font-size: 14px; margin: 0; font-weight: 600;">{{ $comment->user->name ?? 'User' }}</h6>
                            <span style="font-size: 12px; color: var(--text-muted);">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="font-size: 14px; margin: 0; line-height: 1.5; color: var(--text-dark);">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                <div class="post-show-empty-comments">
                    <i class='bx bx-message-rounded-dots' style="font-size: 48px; color: var(--text-light); margin-bottom: 12px;"></i>
                    <p style="color: var(--text-muted); font-size: 14px;">Belum ada komentar. Jadilah yang pertama!</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: WIDGETS -->
    <x-feed-widgets />
</div>

<style>
    .post-show-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 999px;
        color: var(--text-dark);
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        width: fit-content;
    }
    .post-show-back-btn:hover {
        background: var(--primary-light);
        transform: translateX(-4px);
        box-shadow: var(--shadow-soft);
    }
    .post-show-back-btn i {
        font-size: 20px;
        transition: transform 0.2s;
    }
    .post-show-back-btn:hover i {
        transform: translateX(-3px);
    }

    .post-show-card {
        animation: postShowFadeIn 0.4s ease-out;
        padding: 24px;
    }

    .post-show-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-top: 16px;
        margin-top: 16px;
        border-top: 1px solid var(--border-color);
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }
    .post-show-meta i {
        font-size: 16px;
        vertical-align: middle;
    }

    .post-show-comments {
        animation: postShowFadeIn 0.5s ease-out 0.1s both;
    }

    .post-show-comment-form {
        display: flex;
        gap: 12px;
        align-items: center;
        padding-bottom: 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    .post-show-comment-form input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(10, 10, 10, 0.05);
    }

    .post-show-comment-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .post-show-comment-item {
        display: flex;
        gap: 12px;
        animation: postShowSlideUp 0.3s ease-out both;
    }

    .post-show-empty-comments {
        text-align: center;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    @keyframes postShowFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes postShowSlideUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Stagger comment animations */
    .post-show-comment-item:nth-child(1) { animation-delay: 0.05s; }
    .post-show-comment-item:nth-child(2) { animation-delay: 0.1s; }
    .post-show-comment-item:nth-child(3) { animation-delay: 0.15s; }
    .post-show-comment-item:nth-child(4) { animation-delay: 0.2s; }
    .post-show-comment-item:nth-child(5) { animation-delay: 0.25s; }
</style>

<script>
    function toggleLike(postId) {
        fetch(`/desktop/post/${postId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const btn = document.getElementById(`like-btn-${postId}`);
            const icon = document.getElementById(`like-icon-${postId}`);
            const count = document.getElementById(`like-count-${postId}`);

            count.innerText = data.likes_count;

            if (data.liked) {
                btn.classList.add('active');
                icon.classList.remove('bx-heart');
                icon.classList.add('bxs-heart');
                icon.style.color = 'var(--danger)';
            } else {
                btn.classList.remove('active');
                icon.classList.remove('bxs-heart');
                icon.classList.add('bx-heart');
                icon.style.color = '';
            }
        });
    }

    function votePoll(pollId, optionId) {
        fetch(`/desktop/poll/${pollId}/vote`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ option_id: optionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                Swal.fire({ title: 'Gagal', text: data.error, icon: 'error', toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000 });
                return;
            }
            const container = document.getElementById(`poll-container-${pollId}`);
            let html = '';
            data.options.forEach(opt => {
                html += `<div style="margin-bottom: 12px;"><div style="display: flex; justify-content: space-between; margin-bottom: 4px; padding: 0 8px;"><span style="font-weight: 600; font-size: 14px; color: var(--text-dark);">${opt.text}</span><span style="font-weight: 700; font-size: 14px; color: var(--text-dark);">${opt.percent}%</span></div><div style="height: 36px; background: var(--bg-main); border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);"><div style="height: 100%; width: ${opt.percent}%; background: rgba(29, 155, 240, 0.2); transition: width 0.5s;"></div></div></div>`;
            });
            html += `<div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 13px; color: var(--text-muted);"><span>${data.total_votes} suara</span></div>`;
            container.innerHTML = html;
        });
    }

    function toggleBookmark(postId) {
        fetch(`/desktop/post/${postId}/bookmark`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const btn = document.getElementById(`bookmark-btn-${postId}`);
            const icon = document.getElementById(`bookmark-icon-${postId}`);
            if (data.bookmarked) {
                btn.classList.add('active');
                icon.classList.remove('bx-bookmark');
                icon.classList.add('bxs-bookmark');
                icon.style.color = 'var(--primary)';
            } else {
                btn.classList.remove('active');
                icon.classList.remove('bxs-bookmark');
                icon.classList.add('bx-bookmark');
                icon.style.color = '';
            }
        });
    }

    function sharePost(postId) {
        const url = window.location.origin + `/desktop/post/${postId}`;
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({ title: 'Berhasil!', text: 'Link postingan berhasil disalin!', icon: 'success', toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000 });
        });
    }

    function toggleEditMode() {
        const content = document.getElementById('post-content-display');
        const form = document.getElementById('post-edit-form');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            content.style.display = 'none';
        } else {
            form.style.display = 'none';
            content.style.display = 'block';
        }
    }
</script>
@endsection
