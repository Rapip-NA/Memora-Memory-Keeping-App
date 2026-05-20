@extends('layouts.desktop')

@section('content')
@php
    $currentUser = auth()->user() ?? \App\Models\User::first();
@endphp

<div class="content-grid" style="grid-template-columns: 1fr;">
    <div class="gallery-container">
        
        <!-- Gallery Header -->
        <div class="profile-header" style="background: linear-gradient(135deg, var(--text-dark), var(--primary-color)); padding: 40px; border-radius: var(--radius-lg); color: white; margin-bottom: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
            <i class='bx bx-images' style="font-size: 48px; margin-bottom: 16px; opacity: 0.9;"></i>
            <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 8px;">Galeri Kenangan</h2>
            <p style="font-size: 16px; opacity: 0.9; max-width: 600px;">Kumpulan foto kenangan angkatan kita. Bagikan momen tak terlupakanmu di sini.</p>
            <div style="margin-top: 24px;">
                <button class="btn-solid" onclick="openModal('uploadModal')" style="background: white; color: var(--text-dark); display: inline-flex; align-items: center; gap: 8px;"><i class='bx bx-upload'></i> Unggah Foto</button>
            </div>
        </div>

        <!-- Filter/Tabs -->
        @php
            $currentAlbum = request('album', 'Semua');
            $allAlbums = $allAlbums ?? ['Wisuda 2024', 'Malam Keakraban', 'Album Kelas', 'Lainnya'];
        @endphp
        <div style="display: flex; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; overflow-x: auto;">
            <a href="{{ route('desktop.gallery', ['album' => 'Semua']) }}" class="{{ $currentAlbum == 'Semua' ? 'btn-solid' : 'btn-outline' }}" style="{{ $currentAlbum == 'Semua' ? 'padding: 8px 24px; font-size: 14px; text-decoration: none; white-space: nowrap;' : 'padding: 8px 24px; border: none; color: var(--text-muted); font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap;' }}">Semua</a>
            @foreach($allAlbums as $a)
                <a href="{{ route('desktop.gallery', ['album' => $a]) }}" class="{{ $currentAlbum == $a ? 'btn-solid' : 'btn-outline' }}" style="{{ $currentAlbum == $a ? 'padding: 8px 24px; font-size: 14px; text-decoration: none; white-space: nowrap;' : 'padding: 8px 24px; border: none; color: var(--text-muted); font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap;' }}">{{ $a }}</a>
            @endforeach
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

        <!-- Gallery Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
            
            <!-- Dynamic Items -->
            @foreach($photos as $photo)
            @php 
                $isOwner = $currentUser && $photo->user_id == $currentUser->id ? 'true' : 'false'; 
                $caption = addslashes(str_replace("\n", " ", $photo->caption));
            @endphp
            <div onclick="openPreview({{ $photo->id }}, '{{ Storage::url($photo->file_path) }}', '{{ $caption }}', '{{ $photo->user->name ?? 'Anonim' }}', {{ $isOwner }}, '{{ $photo->album ?? 'Lainnya' }}')" style="position: relative; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 1; cursor: pointer; group">
                <img src="{{ Storage::url($photo->file_path) }}" alt="{{ $photo->caption }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; transform: scale(1.01);">
                <div style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; z-index: 2;">
                    {{ $photo->album ?? 'Lainnya' }}
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; color: white;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">{{ $photo->caption ?? 'Tanpa Caption' }}</h5>
                    <p style="margin: 0; font-size: 12px; opacity: 0.8;">Oleh: {{ $photo->user->name ?? 'Anonim' }}</p>
                </div>
            </div>
            @endforeach
            
            <!-- Static Items -->
            @if($currentAlbum == 'Semua' || $currentAlbum == 'Wisuda 2024')
            <div onclick="openPreview(0, 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80', 'Wisuda 2024', 'Jordan Chen', false, 'Wisuda 2024')" style="position: relative; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 1; cursor: pointer; group">
                <img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" alt="Graduation" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; transform: scale(1.01);">
                <div style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; z-index: 2;">
                    Wisuda 2024
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; color: white;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Wisuda 2024</h5>
                    <p style="margin: 0; font-size: 12px; opacity: 0.8;">Oleh: Jordan Chen</p>
                </div>
            </div>
            @endif

            @if($currentAlbum == 'Semua' || $currentAlbum == 'Malam Keakraban')
            <div onclick="openPreview(0, 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80', 'Malam Keakraban', 'Maya', false, 'Malam Keakraban')" style="position: relative; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 1; cursor: pointer; group">
                <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" alt="Friends" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; transform: scale(1.01);">
                <div style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; z-index: 2;">
                    Malam Keakraban
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; color: white;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Malam Keakraban</h5>
                    <p style="margin: 0; font-size: 12px; opacity: 0.8;">Oleh: Maya</p>
                </div>
            </div>
            @endif

            @if($currentAlbum == 'Semua' || $currentAlbum == 'Album Kelas')
            <div onclick="openPreview(0, 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80', 'Nugas Bareng', 'Elena Rossi', false, 'Album Kelas')" style="position: relative; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 1; cursor: pointer; group">
                <img src="https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" alt="Group Study" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; transform: scale(1.01);">
                <div style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; z-index: 2;">
                    Album Kelas
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; color: white;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Nugas Bareng</h5>
                    <p style="margin: 0; font-size: 12px; opacity: 0.8;">Oleh: Elena Rossi</p>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<!-- Upload Photo Modal -->
<div id="uploadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: #f8f9fa; border-radius: 24px; width: 100%; max-width: 420px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
        
        <!-- Header -->
        <div style="background: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f3f4f6;">
            <button type="button" onclick="closeModal('uploadModal')" style="width: 40px; height: 40px; border-radius: 12px; background: white; border: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #333; box-shadow: 0 2px 5px rgba(0,0,0,0.02); flex-shrink: 0;">
                <i class='bx bx-chevron-left' style="font-size: 24px;"></i>
            </button>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #111; text-align: center; flex-grow: 1;">Unggah Foto</h3>
            <button type="button" onclick="document.getElementById('uploadForm').submit()" style="background: #7c3aed; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3); flex-shrink: 0;">Unggah</button>
        </div>

        <!-- Body -->
        <div style="padding: 24px; overflow-y: auto;">
            <form id="uploadForm" action="{{ route('desktop.gallery.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <label id="uploadBox" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 160px; border: 2px dashed #c4b5fd; border-radius: 16px; background: white; margin-bottom: 24px; cursor: pointer; color: #8b5cf6; transition: all 0.2s;">
                    <i id="uploadIcon" class='bx bx-plus' style="font-size: 24px; margin-bottom: 8px;"></i>
                    <span id="uploadText" style="font-size: 14px; font-weight: 500;">Tambah</span>
                    <input type="file" name="photo" accept="image/*" required style="display: none;" onchange="updateFileName(this)">
                </label>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #7c3aed; font-weight: 600; font-size: 14px;">
                        <i class='bx bx-folder'></i> <span>Album</span>
                    </div>
                </div>
                <div style="background: white; border-radius: 16px; padding: 12px 16px; margin-bottom: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                    <select name="album_select" onchange="checkNewAlbum(this, 'newAlbumInputContainer')" style="width: 100%; border: none; outline: none; font-size: 14px; color: #333; font-family: inherit; background: transparent; cursor: pointer;">
                        @foreach($allAlbums as $a)
                            <option value="{{ $a }}" {{ $a == 'Lainnya' ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                        <option value="new_album">+ Tambah Album Baru...</option>
                    </select>
                </div>
                
                <div id="newAlbumInputContainer" style="display: none; background: white; border-radius: 16px; padding: 12px 16px; margin-bottom: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                    <input type="text" name="new_album_name" placeholder="Tulis nama album baru..." style="width: 100%; border: none; outline: none; font-size: 14px; font-family: inherit; color: #333;">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #7c3aed; font-weight: 600; font-size: 14px;">
                        <i class='bx bx-pencil'></i> <span>Keterangan</span>
                    </div>
                </div>
                <div style="background: white; border-radius: 16px; padding: 16px; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                    <textarea name="caption" maxlength="300" rows="3" placeholder="Tulis keterangan foto..." style="width: 100%; border: none; outline: none; resize: none; font-size: 14px; color: #333; font-family: inherit;"></textarea>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 1000; justify-content: center; align-items: center; padding: 20px;">
    <button onclick="closeModal('previewModal')" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 36px; cursor: pointer;"><i class='bx bx-x'></i></button>
    
    <div style="background: white; border-radius: 16px; overflow: visible; display: flex; flex-direction: column; max-width: 600px; width: 100%; max-height: 90vh;">
        <!-- Image Container -->
        <div style="width: 100%; background: #000; display: flex; align-items: center; justify-content: center; flex: 1; overflow: hidden; min-height: 300px; border-radius: 16px 16px 0 0;">
            <img id="previewImage" src="" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
        </div>
        
        <!-- Info Container -->
        <div style="padding: 20px; position: relative; background: #fff; border-radius: 0 0 16px 16px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex-grow: 1;">
                    <div style="display: inline-block; background: var(--bg-main); color: var(--primary); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 8px;" id="previewAlbum"></div>
                    <h3 id="previewCaption" style="margin: 0 0 8px 0; font-size: 16px; color: #111; line-height: 1.4;"></h3>
                    <p id="previewAuthor" style="margin: 0; font-size: 13px; color: #666; font-weight: 500;"></p>
                </div>
                
                <!-- Options Button (Only for owner) -->
                <div style="position: relative; margin-left: 16px;" id="optionsContainer">
                    <button id="previewOptionsBtn" onclick="toggleOptions()" style="display: none; background: #f3f4f6; border: none; font-size: 20px; color: #333; cursor: pointer; padding: 8px; border-radius: 50%; transition: background 0.2s;">
                        <i class='bx bx-dots-vertical-rounded'></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="optionsDropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 160px; overflow: hidden; z-index: 10; border: 1px solid #f0f0f0;">
                        <button onclick="openEditModal()" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; border-bottom: 1px solid #f9fafb; cursor: pointer; font-size: 14px; color: #333; transition: background 0.2s; font-weight: 500; display: flex; align-items: center;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                            <i class='bx bx-edit' style="margin-right: 12px; font-size: 16px;"></i> Edit Caption
                        </button>
                        <form id="deleteForm" method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto ini secara permanen?');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; cursor: pointer; font-size: 14px; color: #ef4444; transition: background 0.2s; font-weight: 500; display: flex; align-items: center;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='white'">
                                <i class='bx bx-trash' style="margin-right: 12px; font-size: 16px;"></i> Delete Photo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1100; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 20px; width: 100%; max-width: 400px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700; color: #111;">Edit Keterangan</h3>
        
        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #333;">Album</label>
                <select id="editAlbumInput" name="album_select" onchange="checkNewAlbum(this, 'editNewAlbumInputContainer')" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e5e7eb; font-size: 14px; outline: none; background: #f9fafb; cursor: pointer;">
                    @foreach($allAlbums as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                    <option value="new_album">+ Tambah Album Baru...</option>
                </select>
            </div>
            
            <div id="editNewAlbumInputContainer" style="display: none; margin-bottom: 16px;">
                <input type="text" id="editNewAlbumName" name="new_album_name" placeholder="Tulis nama album baru..." style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e5e7eb; font-size: 14px; outline: none; background: #f9fafb;">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #333;">Keterangan</label>
                <textarea id="editCaptionInput" name="caption" rows="4" style="width: 100%; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; font-size: 14px; outline: none; resize: none; font-family: inherit; background: #f9fafb;" placeholder="Tulis keterangan baru..."></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModal('editModal'); openModal('previewModal')" style="padding: 10px 20px; background: #f3f4f6; color: #4b5563; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 10px 20px; background: #7c3aed; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        if(id === 'previewModal') {
            document.getElementById('optionsDropdown').style.display = 'none';
        }
    }

    function openPreview(id, url, caption, author, isOwner, album = 'Lainnya') {
        document.getElementById('previewImage').src = url;
        document.getElementById('previewCaption').innerText = caption || 'Tanpa Caption';
        document.getElementById('previewAuthor').innerText = 'Oleh: ' + author;
        document.getElementById('previewAlbum').innerText = album;
        
        const optionsBtn = document.getElementById('previewOptionsBtn');
        const dropdown = document.getElementById('optionsDropdown');
        
        dropdown.style.display = 'none'; // reset dropdown state

        if(isOwner && id > 0) {
            optionsBtn.style.display = 'block';
            document.getElementById('editForm').action = '/desktop/gallery/' + id;
            document.getElementById('deleteForm').action = '/desktop/gallery/' + id;
            document.getElementById('editCaptionInput').value = caption === 'Tanpa Caption' ? '' : caption;
            
            const editAlbumInput = document.getElementById('editAlbumInput');
            let optionExists = false;
            for(let i=0; i<editAlbumInput.options.length; i++) {
                if(editAlbumInput.options[i].value === album) {
                    optionExists = true;
                    break;
                }
            }
            
            if(optionExists) {
                editAlbumInput.value = album;
                document.getElementById('editNewAlbumInputContainer').style.display = 'none';
            } else {
                editAlbumInput.value = 'new_album';
                document.getElementById('editNewAlbumInputContainer').style.display = 'block';
                document.getElementById('editNewAlbumName').value = album;
            }
        } else {
            optionsBtn.style.display = 'none';
        }
        
        openModal('previewModal');
    }

    function toggleOptions() {
        const dropdown = document.getElementById('optionsDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    function openEditModal() {
        closeModal('previewModal');
        openModal('editModal');
    }

    function updateFileName(input) {
        const textSpan = document.getElementById('uploadText');
        const icon = document.getElementById('uploadIcon');
        const box = document.getElementById('uploadBox');
        if (input.files && input.files.length > 0) {
            box.style.border = '2px solid #7c3aed';
            box.style.background = '#f3e8ff';
            icon.className = 'bx bx-check-circle';
            icon.style.color = '#7c3aed';
            textSpan.innerText = 'Foto Siap Diunggah';
            textSpan.style.color = '#7c3aed';
        } else {
            box.style.border = '2px dashed #c4b5fd';
            box.style.background = 'white';
            icon.className = 'bx bx-plus';
            icon.style.color = '#8b5cf6';
            textSpan.innerText = 'Tambah';
            textSpan.style.color = '#8b5cf6';
        }
    }

    function checkNewAlbum(selectElement, targetId) {
        const targetContainer = document.getElementById(targetId);
        if (selectElement.value === 'new_album') {
            targetContainer.style.display = 'block';
            targetContainer.querySelector('input').required = true;
        } else {
            targetContainer.style.display = 'none';
            targetContainer.querySelector('input').required = false;
        }
    }
</script>

@endsection
