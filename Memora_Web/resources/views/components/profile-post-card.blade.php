@props(['post'])

<div class="post-card" style="display: flex; flex-direction: column; height: 100%;">
    <div class="post-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div class="poster-info">
            @php
                $postAvatar = $post->user && $post->user->photo
                    ? (config('filesystems.default') == 's3' ? Storage::disk('s3')->url($post->user->photo) : Storage::url($post->user->photo))
                    : "https://ui-avatars.com/api/?name=".urlencode($post->user->name ?? 'User')."&background=000000&color=fff&size=100";
            @endphp
            <img src="{{ $postAvatar }}" alt="{{ $post->user->name ?? 'User' }}" class="avatar" loading="lazy" style="object-fit: cover;">
            <div>
                <h4>{{ $post->user->name ?? 'User' }}</h4>
                <p>{{ $post->created_at->diffForHumans() }}</p>
            </div>
        </div>
        <!-- Actions Dropdown or Buttons -->
        @if($post->user_id === auth()->id())
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
    <div class="post-content" id="post-content-{{ $post->id }}" style="flex: 1; display: flex; flex-direction: column;">
        <p style="margin-bottom: auto;">{!! nl2br(e($post->content)) !!}</p>
        @if($post->photo)
            @php
                $photos = json_decode($post->photo, true);
            @endphp
            @if(is_array($photos))
                <div style="position: relative; margin-top: 12px; border-radius: 8px; overflow: hidden; width: 100%; aspect-ratio: 1/1;">
                    <div id="prof-slider-{{ $post->id }}" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 0; scrollbar-width: none; height: 100%;">
                        @foreach($photos as $photoUrl)
                            @php
                                $isVideo = preg_match('/\.(mp4|mov|avi|webm|mkv|3gp)$/i', $photoUrl);
                                $mediaUrl = str_starts_with($photoUrl, 'http') ? $photoUrl : (config('filesystems.default') == 's3' ? Storage::disk('s3')->url($photoUrl) : Storage::url($photoUrl));
                            @endphp
                            <div style="flex: 0 0 100%; scroll-snap-align: start; position: relative; height: 100%;">
                                @if($isVideo)
                                    <video preload="metadata" src="{{ $mediaUrl }}" controls style="width: 100%; height: 100%; object-fit: cover; display: block;"></video>
                                @else
                                    <img src="{{ $mediaUrl }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if(count($photos) > 1)
                        <button onclick="document.getElementById('prof-slider-{{ $post->id }}').scrollBy({left: -300, behavior: 'smooth'})" style="position: absolute; top: 50%; left: 4px; transform: translateY(-50%); width: 24px; height: 24px; border-radius: 50%; background: rgba(0,0,0,0.5); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;"><i class='bx bx-chevron-left'></i></button>
                        <button onclick="document.getElementById('prof-slider-{{ $post->id }}').scrollBy({left: 300, behavior: 'smooth'})" style="position: absolute; top: 50%; right: 4px; transform: translateY(-50%); width: 24px; height: 24px; border-radius: 50%; background: rgba(0,0,0,0.5); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;"><i class='bx bx-chevron-right'></i></button>
                        <style>
                            #prof-slider-{{ $post->id }}::-webkit-scrollbar { display: none; }
                        </style>
                    @endif
                </div>
            @else
                @php
                    $isVideoSingle = preg_match('/\.(mp4|mov|avi|webm|mkv|3gp)$/i', $post->photo);
                    $singleMediaUrl = str_starts_with($post->photo, 'http') ? $post->photo : (config('filesystems.default') == 's3' ? Storage::disk('s3')->url($post->photo) : Storage::url($post->photo));
                @endphp
                <div style="margin-top: 12px; width: 100%; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden;">
                    @if($isVideoSingle)
                        <video preload="metadata" src="{{ $singleMediaUrl }}" controls style="width: 100%; height: 100%; object-fit: cover; display: block;"></video>
                    @else
                        <img src="{{ $singleMediaUrl }}" alt="Post Photo" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    @endif
                </div>
            @endif
        @endif
    </div>

    @if($post->user_id === auth()->id())
    <!-- Edit Form Mode (Hidden initially) -->
    <div class="post-edit-form" id="post-edit-{{ $post->id }}" style="display: none; margin-top: 12px;">
        <form action="{{ route('desktop.post.update', $post->id) }}" method="POST">
            @csrf
            @method('PUT')
            <textarea name="content" rows="4" style="width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; background: var(--bg-main); outline: none; margin-bottom: 8px; font-family: inherit;">{{ $post->content }}</textarea>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="cancelEdit({{ $post->id }})" class="btn-solid" style="background: var(--bg-card); color: var(--text-dark); border: 1px solid var(--border-color); padding: 6px 12px; font-size: 13px;">Batal</button>
                <button type="submit" class="btn-solid" style="padding: 6px 12px; font-size: 13px;">Simpan</button>
            </div>
        </form>
    </div>
    @endif

    <div class="post-actions" style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <button class="action-btn"><i class='bx bx-heart'></i> {{ $post->likes_count ?? 0 }}</button>
        <button class="action-btn"><i class='bx bx-comment'></i> {{ $post->comments->count() }}</button>
    </div>
</div>
