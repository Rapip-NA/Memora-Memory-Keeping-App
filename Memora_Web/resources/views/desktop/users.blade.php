@extends('layouts.desktop')

@section('content')
<style>
    .classroom-members-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        color: var(--text-dark);
    }

    .members-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .members-title h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .members-title p {
        color: var(--text-muted);
        margin: 0;
        font-size: 15px;
    }

    .classroom-tag {
        background: rgba(29, 155, 240, 0.1);
        color: var(--primary);
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid rgba(29, 155, 240, 0.15);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Classroom Navigation */
    .classroom-navigation {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 28px;
    }

    .classroom-pill {
        background: var(--bg-card);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    .classroom-pill:hover {
        background: var(--bg-main);
        color: var(--text-dark);
        border-color: var(--text-light);
    }

    .classroom-pill.active {
        background: var(--primary);
        color: var(--primary-inverse);
        border-color: var(--primary);
    }

    /* Search Bar */
    .search-filter-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-soft);
    }

    .search-box {
        display: flex;
        align-items: center;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 10px 20px;
        gap: 12px;
        transition: all 0.2s;
    }

    .search-box:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
    }

    .search-box i {
        color: var(--text-muted);
        font-size: 20px;
    }

    .search-box input {
        border: none;
        background: transparent;
        color: var(--text-dark);
        font-family: inherit;
        font-size: 15px;
        outline: none;
        width: 100%;
    }

    /* Grid of Cards */
    .members-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 24px;
    }

    .member-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 10px rgba(0,0,0,0.01);
    }

    .member-card:hover {
        transform: translateY(-4px);
        border-color: var(--primary);
        box-shadow: var(--shadow-hover);
    }

    .member-avatar-container {
        position: relative;
        margin-bottom: 16px;
    }

    .member-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--bg-card);
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        background: var(--border-color);
    }

    .status-dot {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #22c55e;
        border: 3px solid var(--bg-card);
    }

    .member-name {
        font-size: 18px;
        font-weight: 750;
        color: var(--text-dark);
        margin: 0 0 4px 0;
        transition: color 0.2s;
    }

    .member-card:hover .member-name {
        color: var(--primary);
    }

    .member-nickname {
        font-size: 13px;
        color: var(--text-muted);
        background: var(--bg-main);
        padding: 2px 8px;
        border-radius: 6px;
        margin-bottom: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .member-bio {
        font-size: 14px;
        color: var(--text-muted);
        margin: 0 0 16px 0;
        line-height: 1.4;
        height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .member-details {
        display: flex;
        flex-direction: column;
        gap: 6px;
        width: 100%;
        border-top: 1px solid var(--border-color);
        padding-top: 16px;
        margin-top: auto;
    }

    .detail-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .detail-item i {
        font-size: 16px;
        color: var(--text-light);
    }

    /* Warning/Empty Cards */
    .warning-card {
        background: var(--bg-card);
        border: 1px solid #ffe58f;
        border-radius: 20px;
        padding: 32px;
        text-align: center;
        box-shadow: var(--shadow-soft);
        background-color: rgba(255, 251, 230, 0.2);
    }

    .warning-icon {
        font-size: 48px;
        color: #faad14;
        margin-bottom: 16px;
    }

    .warning-card h3 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
    }

    .warning-card p {
        color: var(--text-muted);
        margin: 0 0 24px 0;
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 40px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
    }

    .empty-icon {
        font-size: 40px;
        color: var(--text-light);
        margin-bottom: 16px;
    }
</style>

<div class="classroom-members-wrapper">
    <!-- Gentle Reminder for Unset Classroom -->
    @if(!$currentUser->classroom_id)
        <div style="background: rgba(250, 173, 20, 0.08); border: 1px solid rgba(250, 173, 20, 0.2); padding: 14px 20px; border-radius: 16px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; font-weight: 600; color: #b78103; font-size: 14px;">
            <span><i class='bx bx-info-circle' style="margin-right: 8px; font-size: 18px; vertical-align: middle;"></i> Anda belum mengatur kelas Anda di profil. Silakan atur kelas agar rekan sekelas mudah menemukan profil Anda.</span>
            <a href="{{ route('desktop.profile') }}" style="background: #faad14; color: white; padding: 6px 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; transition: all 0.2s; box-shadow: 0 4px 10px rgba(250, 173, 20, 0.2);">Atur Kelas</a>
        </div>
    @endif

    <!-- Header -->
    <div class="members-header">
        <div class="members-title">
            <h2>
                <i class='bx bx-group' style="color: var(--primary);"></i> 
                Direktori Alumni
            </h2>
            <p>Jelajahi anggota dan temukan alumni berdasarkan kategori kelas.</p>
        </div>
    </div>

    <!-- Classroom Selection Pills -->
    <div class="classroom-navigation">
        @foreach($classrooms as $cls)
            <a href="{{ route('desktop.users', ['classroom_id' => $cls->id]) }}" 
               class="classroom-pill {{ $classroom && $classroom->id === $cls->id ? 'active' : '' }}">
                <i class='bx bx-chalkboard'></i> {{ $cls->name }}
            </a>
        @endforeach
    </div>

    @if($classroom)
        <!-- Search Form -->
        <div class="search-filter-card">
            <form action="{{ route('desktop.users') }}" method="GET">
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau nama panggilan di kelas {{ $classroom->name }}..." autocomplete="off">
                    @if(request('search'))
                        <a href="{{ route('desktop.users', ['classroom_id' => $classroom->id]) }}" style="color: var(--text-light); font-size: 14px; font-weight: 600; text-decoration: none;">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Members Grid -->
        <div class="members-grid">
            @if($users->count() > 0)
                @foreach($users as $user)
                    <a href="{{ route('desktop.profile', ['user' => $user->id]) }}" class="member-card">
                        <div class="member-avatar-container">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="member-avatar">
                            @if($user->status === 'active')
                                <div class="status-dot"></div>
                            @endif
                        </div>
                        
                        <h4 class="member-name">{{ $user->name }}</h4>
                        @if($user->nickname)
                            <span class="member-nickname">{{ '@' . $user->nickname }}</span>
                        @else
                            <div style="height: 28px;"></div>
                        @endif
                        
                        <p class="member-bio">{{ $user->bio ?: 'Tidak ada deskripsi bio.' }}</p>

                        <div class="member-details">
                            @if($user->city)
                                <div class="detail-item">
                                    <i class='bx bx-map'></i>
                                    <span>{{ $user->city }}</span>
                                </div>
                            @endif
                            @if($user->job)
                                <div class="detail-item">
                                    <i class='bx bx-briefcase'></i>
                                    <span>{{ $user->job }} {{ $user->company ? 'di ' . $user->company : '' }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class='bx bx-user-x'></i>
                    </div>
                    <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700;">Tidak Ada Anggota</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: 14px;">
                        {{ request('search') ? 'Tidak ada alumni di kelas ini yang cocok dengan pencarian Anda.' : 'Belum ada anggota lain yang terdaftar di kelas ini.' }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if($users->count() > 0 && $users->hasPages())
            <div style="margin-top: 32px; display: flex; justify-content: center;">
                {{ $users->links('vendor.pagination.custom') }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class='bx bx-error-circle'></i>
            </div>
            <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700;">Data Kelas Tidak Ditemukan</h4>
            <p style="margin: 0; color: var(--text-muted); font-size: 14px;">Tidak ada kategori kelas yang tersedia di sistem saat ini.</p>
        </div>
    @endif
</div>
@endsection
