@props(['unreadCount', 'sidebarUser', 'sidebarAvatar'])

        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class='bx bx-book-bookmark'></i></div>
                <h2>The Archive</h2>
            </div>
            
            <nav class="main-nav">
                <a href="{{ route('desktop.feed') }}" class="nav-item {{ request()->routeIs('desktop.feed') ? 'active' : '' }}">
                    <i class='bx {{ request()->routeIs('desktop.feed') ? 'bxs-home-circle' : 'bx-home-circle' }}'></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('desktop.explore') }}" class="nav-item {{ request()->routeIs('desktop.explore') ? 'active' : '' }}">
                    <i class='bx bx-search'></i>
                    <span>Explore</span>
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
                <a href="{{ route('desktop.dashboard') }}" class="nav-item {{ request()->routeIs('desktop.dashboard') ? 'active' : '' }}">
                    <i class='bx {{ request()->routeIs('desktop.dashboard') ? 'bxs-bar-chart-alt-2' : 'bx-bar-chart-alt-2' }}'></i>
                    <span>Dashboard Statistic</span>
                </a>

                <a href="#" onclick="toggleChatPanel(); return false;" class="nav-item" style="position: relative;">
                    <i class='bx bx-message-square-dots'></i>
                    <span>Chat</span>
                    <span style="position: absolute; top: 12px; right: 16px; background: var(--danger); color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">3</span>
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
                            <p>Class of '24</p>
                        </div>
                        <i class='bx bx-chevron-right action-icon'></i>
                    </div>
                </a>
                
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                    @csrf
                    <button type="submit" class="nav-item" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left; padding: 12px 16px; display: flex; align-items: center; gap: 16px; color: var(--danger); font-family: inherit; font-size: 15px; font-weight: 600; border-radius: 12px; transition: all 0.2s;">
                        <i class='bx bx-log-out'></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>
