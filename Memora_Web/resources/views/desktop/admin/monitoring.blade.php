@extends('layouts.desktop')

@section('content')
<style>
    .monitoring-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        color: var(--text-dark);
    }
    
    .monitoring-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .monitoring-title h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .monitoring-title p {
        color: var(--text-muted);
        margin: 0;
        font-size: 15px;
    }
    
    .search-box {
        display: flex;
        gap: 12px;
        align-items: center;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        border-radius: 9999px;
        width: 320px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    
    .search-box i {
        font-size: 20px;
        color: var(--text-muted);
    }
    
    .search-box input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        color: var(--text-dark);
        font-family: inherit;
        font-size: 14px;
    }
    
    .monitoring-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    
    .monitoring-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .monitoring-table th {
        background: var(--bg-main);
        padding: 16px 24px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .monitoring-table td {
        padding: 20px 24px;
        font-size: 15px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .monitoring-table tr:last-child td {
        border-bottom: none;
    }
    
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--border-color);
        object-fit: cover;
        border: 1px solid var(--border-color);
    }
    
    .user-details h4 {
        margin: 0;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .user-details p {
        margin: 2px 0 0 0;
        font-size: 12px;
        color: var(--text-muted);
    }
    
    .post-content-cell {
        max-width: 400px;
    }
    
    .post-text-preview {
        font-size: 14px;
        line-height: 1.5;
        color: var(--text-dark);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .media-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(29, 155, 240, 0.1);
        color: #1d9bf0;
        padding: 4px 8px;
        border-radius: 6px;
        margin-top: 6px;
    }

    .poll-badge {
        background: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
    }

    .event-badge {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    
    .metrics-cell {
        display: flex;
        gap: 16px;
        color: var(--text-muted);
        font-size: 13px;
    }
    
    .metric-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .btn-delete {
        background: rgba(255, 77, 79, 0.1);
        color: #ff4d4f;
        border: none;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: inherit;
    }
    
    .btn-delete:hover {
        background: #ff4d4f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 77, 79, 0.25);
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 40px;
    }
    
    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--bg-main);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin: 0 auto 24px;
    }
    
    .empty-state h3 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .empty-state p {
        margin: 0;
        color: var(--text-muted);
        font-size: 15px;
    }
    
    .pagination-container {
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="monitoring-wrapper">
    <div class="monitoring-header">
        <div class="monitoring-title">
            <h2><i class='bx bx-shield-quarter' style="color: var(--primary);"></i> Monitoring Konten</h2>
            <p>Pantau seluruh aktivitas postingan beranda alumni. Hapus konten yang melanggar aturan.</p>
        </div>
        
        <form action="{{ route('admin.monitoring') }}" method="GET" style="margin: 0;">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" name="search" placeholder="Cari isi post atau nama alumni..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.monitoring') }}" style="color: var(--text-muted); display: flex;"><i class='bx bx-x-circle'></i></a>
                @endif
            </div>
        </form>
    </div>
    
    @if(session('success'))
    <div style="background: #38cb89; color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
    </div>
    @endif
    
    <div class="monitoring-card">
        @if($posts->count() > 0)
        <div style="overflow-x: auto;">
            <table class="monitoring-table">
                <thead>
                    <tr>
                        <th>Alumni</th>
                        <th>Konten Postingan</th>
                        <th>Interaksi</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posts as $post)
                    <tr>
                        <td>
                            <div class="user-info-cell">
                                <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="user-avatar">
                                <div class="user-details">
                                    <h4>{{ $post->user->name }}</h4>
                                    <p>{{ $post->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="post-content-cell">
                                @php
                                    $cleanContent = $post->content;
                                    $eventName = null;
                                    $isEvent = false;
                                    if (preg_match('/🎉 Acara: (.*)/', $post->content, $matches)) {
                                        $eventName = trim($matches[1]);
                                        $isEvent = true;
                                        $cleanContent = preg_replace('/🎉 Acara: .*/', '', $cleanContent);
                                    }
                                    if (preg_match('/📅 Tanggal: (.*)/', $cleanContent, $matches)) {
                                        $cleanContent = preg_replace('/📅 Tanggal: .*/', '', $cleanContent);
                                    }
                                    if (preg_match('/📍 Lokasi: (.*)/', $cleanContent, $matches)) {
                                        $cleanContent = preg_replace('/📍 Lokasi: .*/', '', $cleanContent);
                                    }
                                    $cleanContent = trim($cleanContent);
                                @endphp
                                <p class="post-text-preview" title="{{ $post->content }}">
                                    {{ $cleanContent ?: ($isEvent ? '🎉 Acara: ' . $eventName : 'Media upload') }}
                                </p>
                                
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    @if($post->photo && $post->photo !== '[]')
                                        <span class="media-badge">
                                            <i class='bx bx-image'></i> Media
                                        </span>
                                    @endif

                                    @if($post->poll)
                                        <span class="media-badge poll-badge">
                                            <i class='bx bx-poll'></i> Polling
                                        </span>
                                    @endif

                                    @if($isEvent)
                                        <span class="media-badge event-badge">
                                            <i class='bx bx-calendar-event'></i> Acara: {{ $eventName }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="metrics-cell">
                                <div class="metric-item" title="Likes">
                                    <i class='bx bx-heart' style="color: var(--danger);"></i>
                                    <span>{{ $post->likes_count ?? $post->likes->count() }}</span>
                                </div>
                                <div class="metric-item" title="Komentar">
                                    <i class='bx bx-comment' style="color: var(--primary);"></i>
                                    <span>{{ $post->comments->count() }}</span>
                                </div>
                                <div class="metric-item" title="Bookmarks">
                                    <i class='bx bx-bookmark' style="color: #ffab00;"></i>
                                    <span>{{ $post->bookmarks->count() }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <form action="{{ route('admin.monitoring.deletePost', $post->id) }}" method="POST" id="form-delete-post-{{ $post->id }}" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmDeletePost({{ $post->id }})" class="action-btn btn-delete">
                                    <i class='bx bx-trash'></i> Moderasi
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($posts->hasPages())
        <div class="pagination-container">
            {{ $posts->links() }}
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class='bx bx-search-alt-2'></i>
            </div>
            <h3>Postingan Tidak Ditemukan!</h3>
            <p>Tidak ada postingan yang sesuai dengan filter pencarian Anda saat ini.</p>
        </div>
        @endif
    </div>
</div>

<script>
    function confirmDeletePost(postId) {
        Swal.fire({
            title: 'Hapus Postingan Secara Paksa?',
            text: 'Tindakan ini akan menghapus postingan secara permanen dan tidak dapat dibatalkan. Anggota pembuat post tidak akan lagi melihat postingan ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: 'var(--border-color)',
            confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus Post',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal-premium-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`form-delete-post-${postId}`).submit();
            }
        });
    }
</script>
@endsection
