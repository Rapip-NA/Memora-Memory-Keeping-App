@extends('layouts.desktop')

@section('content')
<div class="content-grid" style="grid-template-columns: 1fr;">
    <div class="profile-container">
        
        @if($q)
        <div class="section-header">
            <h3><i class='bx bx-search' style="margin-right: 8px;"></i> Hasil Pencarian untuk: "{{ $q }}"</h3>
        </div>
        
        @if($users->count() > 0)
        <h4 style="margin-top: 16px; margin-bottom: 12px; color: var(--text-main);">Alumni</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            @foreach($users as $user)
            <div style="background: var(--bg-card); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid var(--border-color);">
                @php 
                    $uAvatar = $user->photo 
                        ? Storage::url($user->photo) 
                        : "https://ui-avatars.com/api/?name=".urlencode($user->name)."&background=000000&color=fff&size=100";
                @endphp
                <img src="{{ $uAvatar }}" alt="{{ $user->name }}" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin-bottom: 12px;">
                <h5 style="margin: 0; font-size: 15px;">{{ $user->name }}</h5>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($user->bio ?? 'Alumni', 30) }}</p>
            </div>
            @endforeach
        </div>
        @endif

        <h4 style="margin-top: 16px; margin-bottom: 12px; color: var(--text-main);">Postingan & Acara</h4>
        <div id="posts-container">
            @if($posts->count() > 0)
                @include('desktop.partials.post-list', ['posts' => $posts])
            @else
                <div style="text-align: center; padding: 32px; background: var(--bg-card); border-radius: 16px;">
                    <p style="color: var(--text-muted);">Tidak ada postingan yang sesuai dengan pencarianmu.</p>
                </div>
            @endif
        </div>
        
        @else
        <div class="section-header">
            <h3><i class='bx bx-compass' style="margin-right: 8px;"></i> Explore Postingan</h3>
        </div>
        
        <div id="posts-container">
            @include('desktop.partials.post-list', ['posts' => $posts])
        </div>
        @endif

    </div>
</div>

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

    function toggleComments(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        if (commentsSection.style.display === 'none') {
            commentsSection.style.display = 'block';
        } else {
            commentsSection.style.display = 'none';
        }
    }

    function sharePost(postId) {
        const url = window.location.origin + `/desktop/feed`; 
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link berhasil disalin!', 'success');
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
</script>
@endsection
