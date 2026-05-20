@extends('layouts.desktop')

@section('content')
<div class="content-grid" style="grid-template-columns: 1fr;">
    <div class="profile-container">
        <!-- Profile Card -->
        <div class="profile-card" style="background: var(--bg-card); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--border-color); margin-bottom: 24px;">
            @php 
                $pAvatar = $currentUser->avatar_url;
                $pBanner = $currentUser->banner_url;
            @endphp
            
            <!-- Banner -->
            <div class="profile-banner" style="height: 200px; background: {{ $pBanner ? "url('{$pBanner}') center/cover" : '#CFD9DE' }}; position: relative;">
            </div>
            
            <!-- Info Section -->
            <div class="profile-info" style="padding: 0 16px 16px; position: relative;">

                
                <!-- Top Row: Avatar & Action Button -->
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: -65px; margin-bottom: 12px;">
                    <div style="padding: 4px; background: var(--bg-card); border-radius: 50%; display: inline-block;">
                        <img src="{{ $pAvatar }}" alt="Profile" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; display: block;">
                    </div>
                    
                    <div style="margin-bottom: 10px; display: flex; gap: 8px;">
                        <button id="theme-toggle-btn" onclick="toggleTheme()" style="background: var(--bg-card); color: var(--text-dark); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 999px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.2s;"><i class='bx bx-moon'></i></button>
                        <button onclick="toggleEditProfileModal()" style="background: var(--bg-card); color: var(--text-dark); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 999px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='var(--bg-main)'" onmouseout="this.style.backgroundColor='var(--bg-card)'">Edit profile</button>
                    </div>
                </div>
                
                <!-- Name & Handle -->
                <div style="margin-bottom: 12px;">
                    <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 4px; line-height: 1.2;">
                        {{ $currentUser->name }}
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 15px;">{{ '@' . strtolower(str_replace(' ', '', $currentUser->name)) }}</p>
                </div>
                
                <!-- Bio -->
                <p style="margin: 0 0 12px; color: var(--text-dark); font-size: 15px; line-height: 1.5; max-width: 600px;">
                    {{ $currentUser->bio ?: 'No bio provided.' }}
                </p>
                
                <div style="display: flex; gap: 16px; margin-bottom: 12px; color: var(--text-muted); font-size: 15px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <i class='bx bx-map'></i> {{ $currentUser->city ?: 'Teyvat' }}
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <i class='bx bx-calendar'></i> Joined {{ $currentUser->created_at->format('F Y') }}
                    </div>
                </div>
                

            </div>
        </div>

        @if(session('success'))
        <div style="background: #10b981; color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
        </div>
        @endif

        @if($errors->any())
        <div style="background: #ef4444; color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 24px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="section-header">
            <h3>Posting Saya</h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 16px;">
            @forelse($posts as $post)
            <x-profile-post-card :post="$post" />
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--bg-card); border-radius: 16px;">
                <i class='bx bx-message-square-x' style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <p style="color: var(--text-muted);">Kamu belum membuat postingan apapun.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(2px); opacity: 0; transition: opacity 0.3s ease;">
    <div id="edit-profile-modal-content" style="background: var(--bg-card); border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; display: flex; flex-direction: column; transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; position: sticky; top: 0; background: rgba(255, 255, 255, 0); z-index: 10;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg-card); opacity: 0.9; backdrop-filter: blur(8px); z-index: -1;"></div>
            <div style="display: flex; align-items: center; gap: 32px;">
                <button onclick="toggleEditProfileModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-dark); display: flex; align-items: center; justify-content: center; border-radius: 50%; width: 36px; height: 36px; transition: 0.2s;" onmouseover="this.style.backgroundColor='var(--bg-main)'" onmouseout="this.style.backgroundColor='transparent'"><i class='bx bx-x'></i></button>
                <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--text-dark);">Edit profile</h3>
            </div>
            <button form="edit-profile-form" type="submit" style="background: var(--text-dark); color: var(--bg-card); border: none; padding: 6px 16px; border-radius: 999px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.2s;">Save</button>
        </div>
        
        <form id="edit-profile-form" action="{{ route('desktop.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Banner Section -->
            <div style="position: relative; height: 200px; background: {{ $pBanner ? "url('{$pBanner}') center/cover" : '#CFD9DE' }};" id="banner-preview-container">
                <!-- Camera icon overlay for banner -->
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; gap: 16px;">
                    <label for="banner-photo-input" style="width: 44px; height: 44px; border-radius: 50%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.6)'" onmouseout="this.style.backgroundColor='rgba(0,0,0,0.5)'">
                        <i class='bx bx-camera' style="color: white; font-size: 22px;"></i>
                    </label>
                    <input type="file" name="banner_photo" id="banner-photo-input" style="display: none;" accept="image/*" onchange="previewBanner(event)">
                </div>
            </div>

            <!-- Avatar Section -->
            <div style="padding: 0 16px; position: relative; margin-top: -55px; margin-bottom: 16px;">
                <div style="position: relative; display: inline-block;">
                    <img src="{{ $pAvatar }}" id="profile-preview" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-card); background: white;">
                    <!-- Camera icon overlay for avatar -->
                    <label for="profile-photo-input" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 44px; height: 44px; border-radius: 50%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.6)'" onmouseout="this.style.backgroundColor='rgba(0,0,0,0.5)'">
                        <i class='bx bx-camera' style="color: white; font-size: 22px;"></i>
                    </label>
                    <input type="file" name="photo" id="profile-photo-input" style="display: none;" accept="image/*" onchange="previewAvatar(event)">
                </div>
            </div>

            <!-- Inputs -->
            <div style="padding: 0 16px 24px;">
                <div style="border: 1px solid var(--border-color); border-radius: 4px; padding: 6px 12px; margin-bottom: 24px; position: relative;">
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 2px;">Name</label>
                    <input type="text" name="name" value="{{ $currentUser->name }}" required style="width: 100%; border: none; background: transparent; outline: none; color: var(--text-dark); font-size: 15px; padding: 0;">
                </div>
                
                <div style="border: 1px solid var(--border-color); border-radius: 4px; padding: 6px 12px; margin-bottom: 24px; position: relative;">
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 2px;">Bio</label>
                    <textarea name="bio" rows="3" style="width: 100%; border: none; background: transparent; outline: none; color: var(--text-dark); font-size: 15px; font-family: inherit; resize: none; padding: 0;">{{ $currentUser->bio }}</textarea>
                </div>
                
                <div style="border: 1px solid var(--border-color); border-radius: 4px; padding: 6px 12px; margin-bottom: 24px; position: relative;">
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 2px;">Location</label>
                    <input type="text" name="city" value="{{ $currentUser->city ?: 'Teyvat' }}" style="width: 100%; border: none; background: transparent; outline: none; color: var(--text-dark); font-size: 15px; padding: 0;">
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleEditProfileModal() {
        const modal = document.getElementById('edit-profile-modal');
        const modalContent = document.getElementById('edit-profile-modal-content');
        
        if (modal.style.display === 'none' || !modal.style.display) {
            modal.style.display = 'flex';
            // Trigger reflow
            void modal.offsetWidth;
            modal.style.opacity = '1';
            if(modalContent) modalContent.style.transform = 'translateY(0)';
        } else {
            modal.style.opacity = '0';
            if(modalContent) modalContent.style.transform = 'translateY(20px)';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    function previewAvatar(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function previewBanner(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('banner-preview-container').style.background = `url('${e.target.result}') center/cover`;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function editPost(id) {
        document.getElementById(`post-content-${id}`).style.display = 'none';
        document.getElementById(`post-edit-${id}`).style.display = 'block';
    }

    function cancelEdit(id) {
        document.getElementById(`post-content-${id}`).style.display = 'block';
        document.getElementById(`post-edit-${id}`).style.display = 'none';
    }
</script>
@endsection
