    <!-- Global Compose Post Modal -->
    <div id="global-compose-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); width: 100%; max-width: 600px; border-radius: 20px; padding: 0; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: scaleUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); overflow: hidden; border: 1px solid var(--border-color);">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border-color);">
                <h3 id="modal-title" style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark);">Buat Postingan</h3>
                <button onclick="closeGlobalComposeModal()" style="background: var(--bg-main); border: none; font-size: 20px; cursor: pointer; color: var(--text-muted); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='var(--danger)'; this.style.color='white';" onmouseout="this.style.background='var(--bg-main)'; this.style.color='var(--text-muted)';">&times;</button>
            </div>
            
            <form id="global-compose-form" action="{{ route('desktop.post.store') }}" method="POST" enctype="multipart/form-data" style="padding: 24px;">
                @csrf
                
                <!-- Progress Bar -->
                <div id="global-upload-progress-container" style="display: none; height: 4px; background: var(--bg-main); border-radius: 2px; margin-bottom: 16px; overflow: hidden;">
                    <div id="global-upload-progress-bar" style="height: 100%; width: 0%; background: #1d9bf0; transition: width 0.2s;"></div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <!-- Avatar -->
                    @php 
                        $uAvatar = auth()->user()->avatar_url ?? '';
                    @endphp
                    <img src="{{ $uAvatar }}" alt="User" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                    
                    <div style="flex: 1;">
                        <textarea name="content" id="global-post-content" rows="4" placeholder="Apa yang sedang terjadi?" style="width: 100%; border: none; background: transparent; font-size: 19px; font-family: inherit; color: var(--text-dark); resize: none; outline: none; min-height: 120px;" oninput="validateGlobalPostInput()"></textarea>
                        
                        <!-- Media Preview -->
                        <div id="global-post-preview" style="display: none; position: relative; margin-top: 12px; margin-bottom: 12px; border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color);">
                            <div id="global-photo-slider" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 0; scrollbar-width: none; width: 100%;">
                            </div>
                            <button type="button" onclick="removeGlobalPhoto()" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.7); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); z-index: 10;">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        
                        <input type="file" name="photos[]" id="global-post-photo" style="display: none;" accept="image/*,video/*" multiple onchange="previewGlobalPhoto(event)">

                        <!-- Poll Container (Hidden initially) -->
                        <div id="global-poll-container" style="display: none; margin-top: 12px; padding: 16px; border: 1px solid var(--border-color); border-radius: 16px; background: var(--bg-main);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <span style="font-weight: 700; font-size: 15px; color: var(--text-dark);">Polling</span>
                                <button type="button" onclick="toggleGlobalPoll()" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class='bx bx-x' style="font-size: 20px;"></i></button>
                            </div>
                            <div id="global-poll-options" style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="text" name="poll_options[]" placeholder="Pilihan 1" class="global-poll-input" style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark);" oninput="validateGlobalPostInput()">
                                <input type="text" name="poll_options[]" placeholder="Pilihan 2" class="global-poll-input" style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark);" oninput="validateGlobalPostInput()">
                            </div>
                            <button type="button" onclick="addGlobalPollOption()" style="color: #1d9bf0; background: transparent; border: none; margin-top: 12px; font-weight: 600; cursor: pointer; font-size: 14px;">+ Tambah Pilihan</button>
                        </div>

                        <!-- Event Container (Hidden initially) -->
                        <div id="global-event-container" style="display: none; margin-top: 12px; padding: 16px; border: 1px solid var(--border-color); border-radius: 16px; background: var(--bg-main);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <span style="font-weight: 700; font-size: 15px; color: var(--text-dark);">Detail Acara</span>
                                <button type="button" onclick="toggleGlobalEvent()" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class='bx bx-x' style="font-size: 20px;"></i></button>
                            </div>
                            <input type="text" name="event_name" id="global-event-name" placeholder="Nama Acara" style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark); margin-bottom: 8px;" oninput="validateGlobalPostInput()">
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="date" name="event_date" id="global-event-date" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark);" onchange="validateGlobalPostInput()">
                                <input type="time" name="event_time" id="global-event-time" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark);" onchange="validateGlobalPostInput()">
                            </div>
                            <input type="text" name="event_location" id="global-event-location" placeholder="Lokasi Acara" style="width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); padding: 12px; border-radius: 12px; outline: none; color: var(--text-dark);" oninput="validateGlobalPostInput()">
                        </div>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; gap: 4px;">
                        <button type="button" class="tool-btn" onclick="document.getElementById('global-post-photo').click()" title="Media" style="color: #1d9bf0;"><i class='bx bx-image-alt'></i></button>
                        <button type="button" class="tool-btn" onclick="toggleGlobalPoll()" title="Poll" style="color: #8b5cf6;"><i class='bx bx-poll'></i></button>
                        <button type="button" class="tool-btn" onclick="toggleGlobalEvent()" title="Event" style="color: #f59e0b;"><i class='bx bx-calendar-event'></i></button>
                        <button type="button" class="tool-btn" title="Emoji" style="color: #ffb800;"><i class='bx bx-smile'></i></button>
                    </div>
                    <button type="submit" id="global-submit-btn" class="btn-solid" style="padding: 10px 32px; border-radius: 999px; font-weight: 700; font-size: 15px; opacity: 0.5; pointer-events: none; transition: all 0.3s;">Posting</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .tool-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .tool-btn:hover {
            background: var(--bg-main);
        }
    </style>
