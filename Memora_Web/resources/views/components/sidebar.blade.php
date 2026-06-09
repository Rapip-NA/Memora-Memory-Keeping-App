@props(['unreadCount', 'sidebarUser', 'sidebarAvatar'])

        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class='bx bx-book-bookmark'></i></div>
                <h2>The Archive</h2>
            </div>
            
            <nav class="main-nav">
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <!-- Navigasi Admin -->
                    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('admin.dashboard') ? 'bxs-bar-chart-alt-2' : 'bx-bar-chart-alt-2' }}'></i>
                        <span>Statistik</span>
                    </a>
                    <a href="{{ route('admin.validation') }}" class="nav-item {{ request()->routeIs('admin.validation') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('admin.validation') ? 'bxs-user-check' : 'bx-user-check' }}'></i>
                        <span>Validasi User</span>
                    </a>
                    <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('admin.users*') ? 'bxs-group' : 'bx-group' }}'></i>
                        <span>Anggota</span>
                    </a>
                    <a href="{{ route('admin.monitoring') }}" class="nav-item {{ request()->routeIs('admin.monitoring') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('admin.monitoring') ? 'bxs-shield-quarter' : 'bx-shield-quarter' }}'></i>
                        <span>Monitoring Konten</span>
                    </a>
                    <a href="{{ route('admin.classrooms') }}" class="nav-item {{ request()->routeIs('admin.classrooms*') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('admin.classrooms*') ? 'bxs-chalkboard' : 'bx-chalkboard' }}'></i>
                        <span>Manajemen Kelas</span>
                    </a>
                    <a href="{{ route('desktop.feed') }}" class="nav-item {{ request()->routeIs('desktop.feed') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.feed') ? 'bxs-home-circle' : 'bx-home-circle' }}'></i>
                        <span>Lihat Feed</span>
                    </a>
                @else
                    <!-- Navigasi Member -->
                    <a href="{{ route('desktop.feed') }}" class="nav-item {{ request()->routeIs('desktop.feed') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.feed') ? 'bxs-home-circle' : 'bx-home-circle' }}'></i>
                        <span>Home</span>
                    </a>
                    <a href="{{ route('desktop.explore') }}" class="nav-item {{ request()->routeIs('desktop.explore') ? 'active' : '' }}">
                        <i class='bx bx-search'></i>
                        <span>Explore</span>
                    </a>
                    <a href="{{ route('desktop.users') }}" class="nav-item {{ request()->routeIs('desktop.users') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.users') ? 'bxs-group' : 'bx-group' }}'></i>
                        <span>Anggota</span>
                    </a>
                    <a href="{{ route('desktop.gallery') }}" class="nav-item {{ request()->routeIs('desktop.gallery') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.gallery') ? 'bxs-photo-album' : 'bx-photo-album' }}'></i>
                        <span>Gallery</span>
                    </a>
                    <a href="{{ route('desktop.bookmarks') }}" class="nav-item {{ request()->routeIs('desktop.bookmarks') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.bookmarks') ? 'bxs-bookmark' : 'bx-bookmark' }}'></i>
                        <span>Bookmarks</span>
                    </a>
                    <a href="{{ route('desktop.events') }}" class="nav-item {{ request()->routeIs('desktop.events') ? 'active' : '' }}">
                        <i class='bx {{ request()->routeIs('desktop.events') ? 'bxs-calendar-event' : 'bx-calendar-event' }}'></i>
                        <span>Events</span>
                    </a>
                    <a href="{{ route('desktop.notification') }}" class="nav-item {{ request()->routeIs('desktop.notification') ? 'active' : '' }}" style="position: relative;">
                        <i class='bx {{ request()->routeIs('desktop.notification') ? 'bxs-bell' : 'bx-bell' }}'></i>
                        <span>Notification</span>
                        <span id="sidebar-notif-badge" style="position: absolute; top: 8px; right: 24px; background: var(--danger); color: white; border-radius: 50%; width: 20px; height: 20px; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; display: {{ $unreadCount > 0 ? 'flex' : 'none' }};">{{ $unreadCount > 0 ? $unreadCount : '' }}</span>
                    </a>

                    <div class="create-dropdown-container" style="position: relative; margin-top: 16px;">
                        <button class="primary-btn" id="start-post-btn" onclick="toggleCreateDropdown()" style="width: 100%; justify-content: center; padding: 12px; border-radius: 9999px;">
                            <i class='bx bx-plus'></i> Start Post
                        </button>
                        
                        <div id="create-dropdown-menu" class="create-dropdown" style="display: none; position: absolute; bottom: 100%; left: 0; width: 100%; background: var(--bg-card); border-radius: 16px; padding: 8px; margin-bottom: 12px; border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 100; animation: slideUp 0.2s ease-out;">
                            <div style="padding: 8px 12px; border-bottom: 1px solid var(--border-color); margin-bottom: 8px;">
                                <span style="font-weight: 700; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Create New</span>
                            </div>
                            <a href="#" onclick="openGlobalComposeModal('post'); return false;" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(29, 155, 240, 0.1); color: #1d9bf0;"><i class='bx bx-edit-alt'></i></div>
                                <span>Post</span>
                            </a>
                            <a href="#" onclick="openGlobalComposeModal('media'); return false;" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class='bx bx-image-alt'></i></div>
                                <span>Media</span>
                            </a>
                            <a href="#" onclick="openGlobalComposeModal('event'); return false;" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class='bx bx-calendar-event'></i></div>
                                <span>Event</span>
                            </a>
                            <a href="#" onclick="openGlobalComposeModal('poll'); return false;" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class='bx bx-poll'></i></div>
                                <span>Poll</span>
                            </a>
                        </div>
                    </div>
                @endif

                <style>
                    .create-dropdown-container .primary-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(29, 155, 240, 0.3);
                    }

                    .create-dropdown .dropdown-item {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 10px 12px;
                        border-radius: 12px;
                        text-decoration: none;
                        color: var(--text-primary);
                        transition: all 0.2s;
                        margin-bottom: 4px;
                    }

                    .create-dropdown .dropdown-item:hover {
                        background: var(--bg-main);
                        transform: translateX(4px);
                    }

                    .create-dropdown .item-icon {
                        width: 32px;
                        height: 32px;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 18px;
                    }

                    .create-dropdown .dropdown-item span {
                        font-weight: 600;
                        font-size: 14px;
                    }

                    @keyframes slideUp {
                        from { transform: translateY(10px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>

                <script>
                    function toggleCreateDropdown() {
                        const menu = document.getElementById('create-dropdown-menu');
                        if (menu.style.display === 'none') {
                            menu.style.display = 'block';
                            document.addEventListener('click', closeDropdownOnClickOutside);
                        } else {
                            menu.style.display = 'none';
                        }
                    }

                    function closeDropdownOnClickOutside(event) {
                        const container = document.querySelector('.create-dropdown-container');
                        const menu = document.getElementById('create-dropdown-menu');
                        if (!container.contains(event.target)) {
                            menu.style.display = 'none';
                            document.removeEventListener('click', closeDropdownOnClickOutside);
                        }
                    }
                </script>

            </nav>

            <div class="sidebar-bottom">
                <a href="{{ route('desktop.profile') }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="user-profile {{ request()->routeIs('desktop.profile') ? 'active' : '' }}" style="{{ request()->routeIs('desktop.profile') ? 'background: var(--bg-main);' : '' }}">
                        <div style="position: relative; display: inline-block;">
                            <img src="{{ $sidebarAvatar }}" alt="Current User" class="profile-img" style="object-fit: cover;">
                            @if(auth()->check())
                                <div style="position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background-color: #22c55e; border: 2px solid var(--bg-card); border-radius: 50%; box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.4);"></div>
                            @else
                                <div style="position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background-color: #94a3b8; border: 2px solid var(--bg-card); border-radius: 50%;"></div>
                            @endif
                        </div>
                        <div class="profile-info">
                            <h4>{{ $sidebarUser->name ?? 'User' }}</h4>
                            <p>{{ $sidebarUser->classroom->name ?? 'Kelas tidak diatur' }}</p>
                        </div>
                        <i class='bx bx-chevron-right action-icon'></i>
                    </div>
                </a>
                
                <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
                    @csrf
                </form>
                <button type="button" onclick="openLogoutModal()" class="nav-item" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left; padding: 12px 16px; display: flex; align-items: center; gap: 16px; color: var(--danger, #FF4757); font-family: inherit; font-size: 15px; font-weight: 600; border-radius: 12px; transition: all 0.2s; margin-top: 8px;">
                    <i class='bx bx-log-out'></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

<!-- Logout Confirmation Modal -->
<div id="logout-modal-overlay" class="logout-modal-overlay" style="display: none;">
    <div class="logout-modal-card" id="logout-modal-card">
        <div class="logout-modal-icon">
            <i class='bx bx-log-out-circle'></i>
        </div>
        <h3 class="logout-modal-title">Keluar dari Akun?</h3>
        <p class="logout-modal-desc">Apakah kamu yakin ingin keluar? Kamu perlu masuk kembali untuk mengakses akunmu.</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-btn-cancel" onclick="closeLogoutModal()">
                <i class='bx bx-x'></i>
                Batal
            </button>
            <button type="button" class="logout-btn-confirm" onclick="confirmLogout()">
                <i class='bx bx-log-out'></i>
                Ya, Keluar
            </button>
        </div>
    </div>
</div>

<style>
    .logout-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .logout-modal-overlay.active {
        opacity: 1;
    }

    .logout-modal-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg, 24px);
        padding: 40px 36px 32px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.2);
        transform: scale(0.9) translateY(20px);
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
        opacity: 0;
    }
    .logout-modal-overlay.active .logout-modal-card {
        transform: scale(1) translateY(0);
        opacity: 1;
    }

    .logout-modal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(255, 71, 87, 0.1);
        color: #FF4757;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 20px;
        animation: logoutIconPulse 2s ease-in-out infinite;
    }

    @keyframes logoutIconPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.2); }
        50% { box-shadow: 0 0 0 12px rgba(255, 71, 87, 0); }
    }

    .logout-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .logout-modal-desc {
        font-size: 14px;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 28px;
    }

    .logout-modal-actions {
        display: flex;
        gap: 12px;
    }

    .logout-btn-cancel,
    .logout-btn-confirm {
        flex: 1;
        padding: 12px 20px;
        border-radius: var(--radius-full, 999px);
        font-weight: 600;
        font-size: 14px;
        font-family: inherit;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
    }

    .logout-btn-cancel {
        background: var(--bg-main);
        color: var(--text-dark);
        border: 1px solid var(--border-color);
    }
    .logout-btn-cancel:hover {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .logout-btn-confirm {
        background: #FF4757;
        color: #ffffff;
    }
    .logout-btn-confirm:hover {
        background: #e0394a;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(255, 71, 87, 0.35);
    }
    .logout-btn-confirm:active,
    .logout-btn-cancel:active {
        transform: translateY(0);
    }
</style>

<script>
    function openLogoutModal() {
        const overlay = document.getElementById('logout-modal-overlay');
        overlay.style.display = 'flex';
        // Trigger reflow for animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.classList.add('active');
            });
        });
        document.body.style.overflow = 'hidden';
    }

    function closeLogoutModal() {
        const overlay = document.getElementById('logout-modal-overlay');
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    function confirmLogout() {
        document.getElementById('logout-form').submit();
    }

    // Close modal on overlay click (outside card)
    document.getElementById('logout-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('logout-modal-overlay');
            if (overlay.style.display === 'flex') {
                closeLogoutModal();
            }
        }
    });
</script>
