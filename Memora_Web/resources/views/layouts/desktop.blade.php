<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Archive - Alumni Network</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Leaflet for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Fancybox for Photo Zoom/Lightbox -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

    <!-- Browser Image Compression -->
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        // Initialize theme before body loads to prevent FOUC
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        function toggleTheme() {
            const root = document.documentElement;
            if (root.getAttribute('data-theme') === 'dark') {
                root.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                updateThemeButton('light');
            } else {
                root.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                updateThemeButton('dark');
            }
        }

        function updateThemeButton(theme) {
            const btn = document.getElementById('theme-toggle-btn');
            if (!btn) return;
            if (theme === 'dark') {
                btn.innerHTML = "<i class='bx bx-sun'></i> Light Mode";
            } else {
                btn.innerHTML = "<i class='bx bx-moon'></i> Dark Mode";
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') {
                updateThemeButton('dark');
            }
        });
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Memora 1.png') }}">
</head>
<body>
    @php
        $sidebarUser = auth()->user() ? auth()->user()->load('classroom') : null;
        $sidebarAvatar = $sidebarUser ? $sidebarUser->avatar_url : asset('assets/img/default-avatar.png');
        $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count() : 0;
    @endphp
    <div class="dashboard-container">
        <x-sidebar :unreadCount="$unreadCount" :sidebarUser="$sidebarUser" :sidebarAvatar="$sidebarAvatar" />

        <main class="main-content">


            @yield('content')
            
        </main>
    </div>

    <script src="{{ asset('assets/js/script.js') }}"></script>



    <x-compose-modal />

    <style>
        @keyframes scaleUp {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }


    </style>

    <script>


        // Global Modal Logic
        let globalSelectedPhotos = new DataTransfer();

        function openGlobalComposeModal(type = 'post') {
            const modal = document.getElementById('global-compose-modal');
            const title = document.getElementById('modal-title');
            const content = document.getElementById('global-post-content');
            
            // Reset state
            removeGlobalPhoto();
            document.getElementById('global-poll-container').style.display = 'none';
            document.getElementById('global-event-container').style.display = 'none';
            content.value = '';
            
            // Set mode
            modal.style.display = 'flex';
            
            if (type === 'media') {
                title.innerText = 'Posting Media Baru';
                document.getElementById('global-post-photo').click();
            } else if (type === 'poll') {
                title.innerText = 'Buat Polling';
                toggleGlobalPoll(true);
            } else if (type === 'event') {
                title.innerText = 'Buat Acara Baru';
                toggleGlobalEvent(true);
            } else {
                title.innerText = 'Buat Postingan Baru';
            }

            setTimeout(() => {
                content.focus();
                validateGlobalPostInput();
            }, 100);
        }

        function closeGlobalComposeModal() {
            document.getElementById('global-compose-modal').style.display = 'none';
        }

        function toggleGlobalPoll(forceShow = null) {
            const container = document.getElementById('global-poll-container');
            const isShowing = container.style.display !== 'none';
            const shouldShow = forceShow !== null ? forceShow : !isShowing;
            
            container.style.display = shouldShow ? 'block' : 'none';
            if (shouldShow) {
                document.getElementById('global-event-container').style.display = 'none';
                document.getElementById('modal-title').innerText = 'Buat Polling';
            } else {
                document.getElementById('modal-title').innerText = 'Buat Postingan Baru';
                // Clear inputs
                container.querySelectorAll('input').forEach(input => input.value = '');
            }
            validateGlobalPostInput();
        }

        function toggleGlobalEvent(forceShow = null) {
            const container = document.getElementById('global-event-container');
            const isShowing = container.style.display !== 'none';
            const shouldShow = forceShow !== null ? forceShow : !isShowing;
            
            container.style.display = shouldShow ? 'block' : 'none';
            if (shouldShow) {
                document.getElementById('global-poll-container').style.display = 'none';
                document.getElementById('modal-title').innerText = 'Buat Acara Baru';
            } else {
                document.getElementById('modal-title').innerText = 'Buat Postingan Baru';
                // Clear inputs
                container.querySelectorAll('input').forEach(input => input.value = '');
            }
            validateGlobalPostInput();
        }

        function addGlobalPollOption() {
            const container = document.getElementById('global-poll-options');
            const inputs = container.querySelectorAll('input');
            if (inputs.length >= 4) return;
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'poll_options[]';
            input.placeholder = `Pilihan ${inputs.length + 1}`;
            input.className = 'global-poll-input';
            input.style = 'width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark); margin-top: 8px;';
            input.oninput = validateGlobalPostInput;
            
            container.appendChild(input);
            validateGlobalPostInput();
        }

        function validateGlobalPostInput() {
            const content = document.getElementById('global-post-content').value.trim();
            const photos = document.getElementById('global-post-photo').files;
            const pollActive = document.getElementById('global-poll-container').style.display !== 'none';
            const eventActive = document.getElementById('global-event-container').style.display !== 'none';
            const pollInputs = document.querySelectorAll('.global-poll-input');
            const submitBtn = document.getElementById('global-submit-btn');
            
            let isValid = content.length > 0 || photos.length > 0;
            
            if (pollActive) {
                const filledPolls = Array.from(pollInputs).filter(input => input.value.trim().length > 0);
                isValid = filledPolls.length >= 2;
            } else if (eventActive) {
                const eventName = document.getElementById('global-event-name').value.trim();
                isValid = eventName.length > 0;
            }
            
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.5';
            submitBtn.style.pointerEvents = isValid ? 'auto' : 'none';
        }

        async function previewGlobalPhoto(event) {
            const input = event.target;
            const previewContainer = document.getElementById('global-post-preview');
            const slider = document.getElementById('global-photo-slider');

            if (input.files && input.files.length > 0) {
                slider.innerHTML = '';
                globalSelectedPhotos = new DataTransfer();
                const maxFiles = Math.min(input.files.length, 10);
                
                for(let i=0; i < maxFiles; i++) {
                    let file = input.files[i];
                    globalSelectedPhotos.items.add(file);

                    const imgWrapper = document.createElement('div');
                    imgWrapper.style.flex = '0 0 100%';
                    imgWrapper.style.scrollSnapAlign = 'start';
                    imgWrapper.style.position = 'relative';

                    const objectUrl = URL.createObjectURL(file);
                    if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = objectUrl;
                        video.controls = true;
                        video.style.width = '100%';
                        video.style.maxHeight = '350px';
                        video.style.objectFit = 'cover';
                        video.style.display = 'block';
                        imgWrapper.appendChild(video);
                    } else {
                        const img = document.createElement('img');
                        img.src = objectUrl;
                        img.style.width = '100%';
                        img.style.maxHeight = '350px';
                        img.style.objectFit = 'cover';
                        img.style.display = 'block';
                        imgWrapper.appendChild(img);
                    }
                    slider.appendChild(imgWrapper);
                }
                
                input.files = globalSelectedPhotos.files;
                previewContainer.style.display = 'block';
                validateGlobalPostInput();
            }
        }

        function removeGlobalPhoto() {
            const input = document.getElementById('global-post-photo');
            input.value = '';
            globalSelectedPhotos = new DataTransfer();
            document.getElementById('global-photo-slider').innerHTML = '';
            document.getElementById('global-post-preview').style.display = 'none';
            validateGlobalPostInput();
        }

        // Handle Global Form Submit via AJAX
        document.getElementById('global-compose-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('global-submit-btn');
            const progressBar = document.getElementById('global-upload-progress-bar');
            const progressContainer = document.getElementById('global-upload-progress-container');
            
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.innerText = 'Mengirim...';
            progressContainer.style.display = 'block';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    progressBar.style.width = '100%';
                    
                    setTimeout(() => {
                        closeGlobalComposeModal();
                        if (response.html) {
                            const container = document.getElementById('posts-container');
                            if (container) {
                                container.insertAdjacentHTML('afterbegin', response.html);
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            }
                        }
                        if (typeof showToast === 'function') showToast('Postingan berhasil dikirim!', 'success');
                        
                        // Reset Form
                        form.reset();
                        removeGlobalPhoto();
                        progressContainer.style.display = 'none';
                        progressBar.style.width = '0%';
                        submitBtn.innerText = 'Posting';
                    }, 500);
                } else {
                    alert('Gagal mengirim postingan.');
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerText = 'Posting';
                    progressContainer.style.display = 'none';
                }
            };

            xhr.send(formData);
        });

        // Close when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('global-compose-modal');
            if (event.target === modal) {
                closeGlobalComposeModal();
            }
        });

        // Custom Photo Click Handler (Handles Double-Tap vs Single-Tap)
        let clickTimer = null;

        function handlePhotoClick(event, postId, element) {
            event.preventDefault();
            
            if (clickTimer === null) {
                // First click
                clickTimer = setTimeout(() => {
                    clickTimer = null;
                    
                    // Single Click -> Open Fancybox Lightbox
                    const galleryName = element.getAttribute('data-gallery');
                    const galleryElements = document.querySelectorAll(`[data-gallery="${galleryName}"]`);
                    
                    const items = Array.from(galleryElements).map(el => {
                        const src = el.getAttribute('data-src');
                        const isVideo = src.match(/\.(mp4|mov|avi|webm)$/i);
                        return {
                            src: src,
                            type: isVideo ? "video" : "image"
                        };
                    });
                    
                    const index = Array.from(galleryElements).indexOf(element);
                    
                    Fancybox.show(items, {
                        startIndex: index !== -1 ? index : 0,
                        Toolbar: { display: ["zoom", "close"] },
                        Images: { initialSize: "fit" }
                    });
                }, 250); // 250ms window for a double tap
            } else {
                // Second click
                clearTimeout(clickTimer);
                clickTimer = null;
                
                // Double Click -> Trigger Like
                triggerDoubleTapLike(postId, element);
            }
        }

        // Double Tap to Like
        function triggerDoubleTapLike(postId, element) {
            const heartIcon = element.querySelector('.heart-animation');
            if (heartIcon) {
                heartIcon.style.transform = 'translate(-50%, -50%) scale(1.2)';
                heartIcon.style.opacity = '1';
                
                setTimeout(() => {
                    heartIcon.style.transform = 'translate(-50%, -50%) scale(0)';
                    heartIcon.style.opacity = '0';
                }, 800);
            }
            
            const likeBtn = document.getElementById(`like-btn-${postId}`);
            if (likeBtn && !likeBtn.classList.contains('active')) {
                if (typeof toggleLike === 'function') {
                    toggleLike(postId);
                }
            }
        }

        // Slider Navigation
        function scrollSlider(postId, direction) {
            const slider = document.getElementById(`slider-${postId}`);
            if (!slider) return;
            const scrollAmount = slider.clientWidth;
            slider.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        }

        function updateSliderDots(postId) {
            const slider = document.getElementById(`slider-${postId}`);
            if (!slider) return;
            
            // Determine current index based on scroll position
            const index = Math.round(slider.scrollLeft / slider.clientWidth);
            
            const dotsContainer = document.getElementById(`dots-${postId}`);
            if (dotsContainer) {
                const dots = dotsContainer.querySelectorAll('.dot');
                dots.forEach((dot, i) => {
                    if (i === index) {
                        dot.style.background = '#fff';
                    } else {
                        dot.style.background = 'rgba(255,255,255,0.4)';
                    }
                });
            }
            
            // Toggle arrow visibility
            const prevBtn = slider.parentElement.querySelector('.slider-btn.prev');
            const nextBtn = slider.parentElement.querySelector('.slider-btn.next');
            
            if (prevBtn) {
                prevBtn.style.display = index === 0 ? 'none' : 'flex';
            }
            
            if (nextBtn) {
                const maxIndex = slider.children.length - 1;
                nextBtn.style.display = index >= maxIndex ? 'none' : 'flex';
            }
        }
    </script>

    <!-- Toast Notification Container -->
    <div id="toast-container" style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; gap: 8px; pointer-events: none;"></div>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Icon based on type
            let icon = "<i class='bx bx-check-circle' style='font-size: 20px;'></i>";
            if(type === 'error') icon = "<i class='bx bx-error-circle' style='font-size: 20px;'></i>";
            
            toast.innerHTML = `<div style="display: flex; align-items: center; gap: 8px;">${icon} <span>${message}</span></div>`;
            
            // Base styles
            toast.style.background = type === 'success' ? 'var(--primary, #10b981)' : 'var(--danger, #ef4444)';
            toast.style.color = 'white';
            toast.style.padding = '12px 24px';
            toast.style.borderRadius = '30px';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            toast.style.fontWeight = '600';
            toast.style.fontSize = '14px';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            toast.style.transition = 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            toast.style.pointerEvents = 'auto';
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 10);
            
            // Animate out and remove
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    </script>

    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="{{ route('desktop.feed') }}" class="bottom-nav-item {{ request()->routeIs('desktop.feed') ? 'active' : '' }}">
            <i class='bx {{ request()->routeIs('desktop.feed') ? 'bxs-home-circle' : 'bx-home-circle' }}'></i>
            <span>Home</span>
        </a>
        <a href="{{ route('desktop.explore') }}" class="bottom-nav-item {{ request()->routeIs('desktop.explore') ? 'active' : '' }}">
            <i class='bx bx-search'></i>
            <span>Explore</span>
        </a>
        <div class="bottom-nav-item" onclick="openGlobalComposeModal()" style="cursor: pointer; color: var(--primary);">
            <i class='bx bxs-plus-circle' style="font-size: 32px; transform: translateY(-8px);"></i>
        </div>
        <a href="{{ route('desktop.notification') }}" class="bottom-nav-item {{ request()->routeIs('desktop.notification') ? 'active' : '' }}" style="position: relative;">
            <i class='bx {{ request()->routeIs('desktop.notification') ? 'bxs-bell' : 'bx-bell' }}'></i>
            <span>Notif</span>
            <span id="mobile-notif-badge" style="position: absolute; top: 0; right: 12px; background: var(--danger); color: white; border-radius: 50%; width: 14px; height: 14px; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; display: {{ $unreadCount > 0 ? 'flex' : 'none' }};">{{ $unreadCount > 0 ? $unreadCount : '' }}</span>
        </a>
        <a href="{{ route('desktop.profile') }}" class="bottom-nav-item {{ request()->routeIs('desktop.profile') ? 'active' : '' }}">
            <img src="{{ $sidebarAvatar }}" alt="Profile" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);">
            <span>Profile</span>
        </a>
    </nav>

    <script>
        let lastNotifCount = {{ $unreadCount ?? 0 }};

        function updateNotificationBadges() {
            fetch('{{ route('desktop.notifications.count') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const count = data.count;
                const sidebarBadge = document.getElementById('sidebar-notif-badge');
                const mobileBadge = document.getElementById('mobile-notif-badge');
                
                if (count > 0) {
                    if (sidebarBadge) { sidebarBadge.style.display = 'flex'; sidebarBadge.innerText = count; }
                    if (mobileBadge) { mobileBadge.style.display = 'flex'; mobileBadge.innerText = count; }
                    
                    // Jika ada notifikasi baru, munculkan popup toast
                    if (count > lastNotifCount && typeof showToast === 'function') {
                        showToast('Ada pemberitahuan baru!', 'success');
                    }
                } else {
                    if (sidebarBadge) { sidebarBadge.style.display = 'none'; }
                    if (mobileBadge) { mobileBadge.style.display = 'none'; }
                }
                
                lastNotifCount = count;
            })
            .catch(error => console.error('Error fetching notifications:', error));
        }

        // Fetch new notifications every 30 seconds for a real-time feel
        setInterval(updateNotificationBadges, 30000);
    </script>
</body>
</html>
