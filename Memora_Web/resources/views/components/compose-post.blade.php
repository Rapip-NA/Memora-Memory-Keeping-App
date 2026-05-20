@props(['currentUser'])
<form id="compose-post-form" action="{{ route('desktop.post.store') }}" method="POST" enctype="multipart/form-data" class="twitter-compose-box" style="margin-bottom: 24px; padding: 24px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
    @csrf
    
    <!-- Progress Bar -->
    <div id="upload-progress-container" style="display: none; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: var(--bg-main);">
        <div id="upload-progress-bar" style="height: 100%; width: 0%; background: #1d9bf0; transition: width 0.2s;"></div>
    </div>
    <div id="upload-status-text" style="display: none; position: absolute; bottom: 8px; right: 16px; font-size: 11px; color: #1d9bf0; font-weight: 600;">Mengunggah... 0%</div>
    <div style="display: flex; gap: 12px;">
        <!-- Left: Avatar -->
        <div>
            @php 
                $uAvatar = $currentUser->avatar_url;
            @endphp
            <img src="{{ $uAvatar }}" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
        </div>
        
        <!-- Right: Input & Actions -->
        <div style="flex: 1; padding-top: 4px;">
            <textarea name="content" id="post-content" rows="1" placeholder="What's happening?" style="width: 100%; border: none; background: transparent; font-size: 20px; font-family: inherit; color: var(--text-dark); resize: none; outline: none; min-height: 52px; overflow: hidden;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
            
            <!-- Image Preview Area -->
            <div id="post-preview" style="display: none; position: relative; margin-top: 12px; margin-bottom: 12px;">
                <div id="post-photo-slider" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 8px; scrollbar-width: none; border-radius: 16px; width: 100%;">
                </div>
                <style>
                    #post-photo-slider::-webkit-scrollbar { display: none; }
                </style>
                
                <!-- Overlay Buttons -->
                <div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                    <button type="button" onclick="removePhoto()" style="background: rgba(0,0,0,0.7); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                
                <!-- Below Image Actions -->
                <div style="display: flex; gap: 16px; margin-top: 12px;">
                    <button type="button" style="background: transparent; border: none; color: var(--text-dark); font-size: 14px; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 0;">
                        <i class='bx bx-user' style="font-size: 18px;"></i> Tag people
                    </button>
                    <button type="button" style="background: transparent; border: none; color: var(--text-dark); font-size: 14px; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 0;">
                        <i class='bx bx-file' style="font-size: 18px;"></i> Add description
                    </button>
                </div>
            </div>
            
            <input type="file" name="photos[]" id="post-photo" style="display: none;" accept="image/*,video/*" multiple>
            
            <!-- Toolbar & Submit -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 12px;">
                <!-- Tools -->
                <div style="display: flex; gap: 4px;">
                    <button type="button" onclick="document.getElementById('post-photo').click()" style="color: #1d9bf0; background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'" title="Media">
                        <i class='bx bx-image-alt'></i>
                    </button>
                    <button type="button" onclick="toggleEmojiPicker()" style="color: #1d9bf0; background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'" title="Emoji">
                        <i class='bx bx-smile'></i>
                    </button>
                    <button type="button" onclick="toggleEventInput()" style="color: #1d9bf0; background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'" title="Event">
                        <i class='bx bx-calendar-event'></i>
                    </button>
                    <button type="button" onclick="togglePollInput()" style="color: #1d9bf0; background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'" title="Poll">
                        <i class='bx bx-poll'></i>
                    </button>
                </div>
                
                <!-- Right tools: post -->
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div id="thread-plus-btn" style="display: none; width: 1px; height: 24px; background: var(--border-color); margin-right: 4px;"></div>
                    <button type="button" id="thread-plus-icon" onclick="document.getElementById('post-photo').click()" style="display: none; color: #1d9bf0; background: transparent; border: 1px solid var(--border-color); width: 28px; height: 28px; border-radius: 50%; align-items: center; justify-content: center; font-size: 18px; cursor: pointer;" title="Tambah Foto Lainnya">
                        <i class='bx bx-plus'></i>
                    </button>
                    <button type="submit" id="submit-post-btn" style="background: rgba(29, 155, 240, 0.5); color: rgba(255, 255, 255, 0.8); border: none; padding: 8px 20px; border-radius: 9999px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.3s; pointer-events: none;">
                        Post
                    </button>
                </div>
            </div>

            <!-- Existing Event hidden inputs & picker -->
            <input type="hidden" name="event_name" id="post-event">
            <input type="hidden" name="event_date" id="post-event-date">
            <input type="hidden" name="event_time" id="post-event-time">
            <input type="hidden" name="event_location" id="post-event-location">
            
            <div id="event-preview" style="background: var(--bg-main); padding: 8px 12px; border-radius: 8px; display: none; align-items: center; gap: 8px; margin-top: 12px;">
                <i class='bx bx-calendar-event' style="color: #1d9bf0;"></i>
                <span id="event-text" style="font-size: 13px;"></span>
                <button type="button" onclick="removeEvent()" style="background: none; border: none; color: var(--danger); cursor: pointer; margin-left: auto;"><i class='bx bx-x'></i></button>
            </div>
            
            <!-- Simple Emoji Picker -->
            <div id="emoji-picker" style="display: none; position: absolute; margin-top: 8px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10; grid-template-columns: repeat(5, 1fr); gap: 4px;">
            </div>

            <!-- Poll Input Container -->
            <div id="poll-input-container" style="display: none; margin-top: 12px; padding: 12px; border: 1px solid var(--border-color); border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-weight: 700; font-size: 15px; color: var(--text-dark);">Polling</span>
                    <button type="button" onclick="removePoll()" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class='bx bx-x' style="font-size: 20px;"></i></button>
                </div>
                
                <div id="poll-options-container" style="display: flex; flex-direction: column; gap: 8px;">
                    <div class="poll-option-row" style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" name="poll_options[]" placeholder="Pilihan 1" class="poll-option-input" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 12px; border-radius: 8px; outline: none; color: var(--text-dark);" oninput="validatePostInput()">
                    </div>
                    <div class="poll-option-row" style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" name="poll_options[]" placeholder="Pilihan 2" class="poll-option-input" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 12px; border-radius: 8px; outline: none; color: var(--text-dark);" oninput="validatePostInput()">
                    </div>
                </div>
                
                <button type="button" id="add-poll-option-btn" onclick="addPollOption()" style="color: #1d9bf0; background: transparent; border: none; padding: 8px 0; margin-top: 8px; font-size: 14px; cursor: pointer; text-align: left; display: block; width: 100%;">+ Tambah pilihan</button>

                <div style="margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                    <span style="display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">Durasi Polling</span>
                    <div style="display: flex; gap: 8px;">
                        <select name="poll_duration_days" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 8px 12px; border-radius: 8px; outline: none; color: var(--text-dark); cursor: pointer;">
                            <option value="1">1 Hari</option>
                            <option value="3">3 Hari</option>
                            <option value="7">7 Hari</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Event Input Modal/Dropdown -->
            <div id="event-input-container" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <input type="text" id="event-name-input" placeholder="Nama Acara..." style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-main); padding: 8px 12px; border-radius: 8px; outline: none; margin-bottom: 8px; color: var(--text-dark);">
                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <input type="date" id="event-date-input" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 8px 12px; border-radius: 8px; outline: none; color: var(--text-dark);">
                    <input type="time" id="event-time-input" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 8px 12px; border-radius: 8px; outline: none; color: var(--text-dark);">
                </div>
                <input type="text" id="event-location-input" placeholder="Nama Lokasi..." style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-main); padding: 8px 12px; border-radius: 8px; outline: none; margin-bottom: 8px; color: var(--text-dark);">
                
                <!-- Map Container -->
                <div id="event-map" style="height: 150px; width: 100%; border-radius: 8px; margin-bottom: 8px; z-index: 1;"></div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: -4px; margin-bottom: 8px;">* Geser pin atau klik pada peta untuk menentukan lokasi tepatnya.</p>
                
                <button type="button" onclick="saveEvent()" class="btn-solid" style="margin-top: 8px; padding: 6px 12px; font-size: 12px;">Tambah Acara</button>
            </div>
        </div>
    </div>
</form>

<!-- Crop Modal -->
<div id="crop-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); padding: 20px; border-radius: 16px; width: 90%; max-width: 600px;">
        <h3 style="margin-top: 0; margin-bottom: 16px; font-weight: 700;">Edit / Crop Gambar</h3>
        <div style="max-height: 60vh; overflow: hidden; margin-bottom: 16px; display: flex; justify-content: center; background: #000;">
            <img id="crop-image-target" style="max-width: 100%; max-height: 60vh; display: block;">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="closeCropModal()" style="padding: 8px 16px; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 8px; cursor: pointer;">Batal</button>
            <button type="button" onclick="applyCrop()" style="padding: 8px 16px; background: #1d9bf0; border: none; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">Terapkan</button>
        </div>
    </div>
</div>
