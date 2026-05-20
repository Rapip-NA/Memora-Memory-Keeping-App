@extends('layouts.desktop')

@section('content')
<div class="content-grid" style="grid-template-columns: 1fr;">
    <div class="profile-container">
        <div class="section-header">
            <h3><i class='bx bx-bell' style="margin-right: 8px;"></i> Notifikasi Anda</h3>
        </div>

        <div style="background: var(--bg-card); border-radius: 16px; padding: 16px; margin-top: 16px;">
            @forelse($notifications as $notif)
            <div style="display: flex; gap: 16px; align-items: flex-start; padding: 16px; border-bottom: 1px solid var(--border-color); background: {{ $notif->read_at ? 'transparent' : 'rgba(123, 97, 255, 0.05)' }}; border-radius: 8px; margin-bottom: 8px;">
                @php 
                    $actorPhoto = isset($notif->data['actor_photo']) && $notif->data['actor_photo'] 
                        ? Storage::url($notif->data['actor_photo']) 
                        : "https://ui-avatars.com/api/?name=".urlencode($notif->data['actor_name'] ?? 'User')."&background=random&color=fff&size=50";
                @endphp
                
                <img src="{{ $actorPhoto }}" alt="User" class="avatar" style="width: 48px; height: 48px; object-fit: cover;">
                
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 14px; color: var(--text-main);">
                        {!! $notif->data['message'] ?? 'Kamu mendapat notifikasi baru.' !!}
                    </p>
                    <span style="font-size: 12px; color: var(--text-muted); margin-top: 4px; display: block;">
                        <i class='bx {{ $notif->type == 'like' ? 'bxs-heart' : 'bxs-comment' }}' style="color: {{ $notif->type == 'like' ? 'var(--danger)' : 'var(--primary)' }}; margin-right: 4px;"></i>
                        {{ $notif->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>
            @empty
            <div style="text-align: center; padding: 48px;">
                <i class='bx bx-bell-off' style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <p style="color: var(--text-muted);">Belum ada notifikasi saat ini.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
