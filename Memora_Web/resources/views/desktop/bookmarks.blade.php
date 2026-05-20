@extends('layouts.desktop')

@section('content')
<div class="content-grid" style="grid-template-columns: 1fr;">
    <div class="profile-container">
        <div class="section-header">
            <h3><i class='bx bx-bookmark' style="margin-right: 8px;"></i> Bookmark Tersimpan</h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 16px;">
            @forelse($posts as $post)
            <div class="post-card">
                <div class="post-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div class="poster-info">
                        @php
                            $bAvatar = $post->user && $post->user->photo
                                ? Storage::url($post->user->photo)
                                : "https://ui-avatars.com/api/?name=".urlencode($post->user->name ?? 'User')."&background=000000&color=fff&size=100";
                        @endphp
                        <img src="{{ $bAvatar }}" alt="{{ $post->user->name ?? 'User' }}" class="avatar" style="object-fit: cover;">
                        <div>
                            <h4>{{ $post->user->name ?? 'User' }}</h4>
                            <p>{{ $post->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Display Mode -->
                <div class="post-content">
                    <p>{!! nl2br(e($post->content)) !!}</p>
                    @if($post->photo)
                        @php
                            $photos = json_decode($post->photo, true);
                        @endphp
                        @if(is_array($photos))
                            <div style="position: relative; margin-top: 12px; border-radius: 12px; overflow: hidden; width: 100%; border: 1px solid var(--border-color);">
                                <div id="bm-slider-{{ $post->id }}" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 0; scrollbar-width: none;">
                                    @foreach($photos as $photoUrl)
                                        @php
                                            $isVideo = preg_match('/\.(mp4|mov|avi|webm)$/i', $photoUrl);
                                        @endphp
                                        <div style="flex: 0 0 100%; scroll-snap-align: start; position: relative;">
                                            @if($isVideo)
                                                <video preload="metadata" src="{{ Storage::url($photoUrl) }}" controls style="width: 100%; height: auto; max-height: 800px; object-fit: cover; display: block;"></video>
                                            @else
                                                <img src="{{ Storage::url($photoUrl) }}" loading="lazy" style="width: 100%; height: auto; max-height: 800px; object-fit: cover; display: block;">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if(count($photos) > 1)
                                    <button onclick="document.getElementById('bm-slider-{{ $post->id }}').scrollBy({left: -300, behavior: 'smooth'})" style="position: absolute; top: 50%; left: 8px; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.5); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;"><i class='bx bx-chevron-left' style="font-size: 24px;"></i></button>
                                    <button onclick="document.getElementById('bm-slider-{{ $post->id }}').scrollBy({left: 300, behavior: 'smooth'})" style="position: absolute; top: 50%; right: 8px; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.5); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;"><i class='bx bx-chevron-right' style="font-size: 24px;"></i></button>
                                    <style>
                                        #bm-slider-{{ $post->id }}::-webkit-scrollbar { display: none; }
                                    </style>
                                @endif
                            </div>
                        @else
                            @php
                                $isVideoSingle = preg_match('/\.(mp4|mov|avi|webm)$/i', $post->photo);
                            @endphp
                            <div style="margin-top: 12px;">
                                @if($isVideoSingle)
                                    <video preload="metadata" src="{{ Storage::url($post->photo) }}" controls style="border-radius: 12px; width: 100%; height: auto; max-height: 800px; object-fit: cover; border: 1px solid var(--border-color); display: block;"></video>
                                @else
                                    <img src="{{ Storage::url($post->photo) }}" alt="Post Photo" loading="lazy" style="border-radius: 12px; width: 100%; height: auto; max-height: 800px; object-fit: cover; border: 1px solid var(--border-color); display: block;">
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                <div class="post-actions" style="margin-top: 16px;">
                    <button class="action-btn"><i class='bx bxs-heart' style="color: var(--danger);"></i> {{ $post->likes_count ?? 0 }}</button>
                    <button class="action-btn"><i class='bx bx-comment'></i> {{ $post->comments->count() }}</button>
                    <a href="{{ route('desktop.feed') }}" class="action-btn ms-auto" style="text-decoration: none;"><i class='bx bx-link-external'></i> Lihat di Feed</a>
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--bg-card); border-radius: 16px;">
                <i class='bx bx-bookmark-minus' style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <p style="color: var(--text-muted);">Belum ada postingan yang kamu simpan.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
