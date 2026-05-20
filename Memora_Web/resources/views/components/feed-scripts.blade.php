@props(['posts'])
<script>
    const emojis = ['😀', '😂', '😍', '🙏', '🔥', '🎉', '👍', '😢', '🤔', '🙌', '✨', '❤️'];
    const emojiPicker = document.getElementById('emoji-picker');
    const postContent = document.getElementById('post-content');
    
    // Post Validation Logic
    const submitPostBtn = document.getElementById('submit-post-btn');
    const threadPlusBtn = document.getElementById('thread-plus-btn');
    const threadPlusIcon = document.getElementById('thread-plus-icon');
    const postPhotoInput = document.getElementById('post-photo');

    function validatePostInput() {
        const hasText = postContent && postContent.value.trim().length > 0;
        const hasPhoto = postPhotoInput && postPhotoInput.files && postPhotoInput.files.length > 0;
        
        let hasPoll = false;
        const pollContainer = document.getElementById('poll-input-container');
        if (pollContainer && pollContainer.style.display !== 'none') {
            const pollInputs = document.querySelectorAll('.poll-option-input');
            let filledCount = 0;
            pollInputs.forEach(input => {
                if (input.value.trim().length > 0) filledCount++;
            });
            hasPoll = filledCount >= 2;
        }
        
        if (hasText || hasPhoto || hasPoll) {
            if(submitPostBtn) {
                submitPostBtn.style.background = '#1d9bf0';
                submitPostBtn.style.color = '#ffffff';
                submitPostBtn.style.boxShadow = '0 4px 12px rgba(29, 155, 240, 0.3)';
                submitPostBtn.style.pointerEvents = 'auto';
            }
            if (threadPlusBtn) threadPlusBtn.style.display = 'block';
            if (threadPlusIcon) threadPlusIcon.style.display = 'flex';
        } else {
            if(submitPostBtn) {
                submitPostBtn.style.background = 'rgba(29, 155, 240, 0.5)';
                submitPostBtn.style.color = 'rgba(255, 255, 255, 0.8)';
                submitPostBtn.style.boxShadow = 'none';
                submitPostBtn.style.pointerEvents = 'none';
            }
            if (threadPlusBtn) threadPlusBtn.style.display = 'none';
            if (threadPlusIcon) threadPlusIcon.style.display = 'none';
        }
    }

    if (postContent) {
        postContent.addEventListener('input', validatePostInput);
    }
    
    if(emojiPicker) {
        emojiPicker.style.display = 'none';
        emojis.forEach(emoji => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = emoji;
            btn.style.cssText = 'background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px;';
            btn.onclick = () => {
                postContent.value += emoji;
                emojiPicker.style.display = 'none';
                validatePostInput();
            };
            emojiPicker.appendChild(btn);
        });
    }

    function toggleEmojiPicker() {
        if(emojiPicker.style.display === 'none') {
            emojiPicker.style.display = 'grid';
        } else {
            emojiPicker.style.display = 'none';
        }
    }

    let mapInitialized = false;
    let eventMap = null;
    let mapMarker = null;

    function toggleEventInput() {
        const container = document.getElementById('event-input-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            
            // Initialize map if not yet initialized
            if (!mapInitialized) {
                // Wait for the container to render
                setTimeout(() => {
                    // Default to Jakarta, or somewhere relevant
                    eventMap = L.map('event-map').setView([-6.200000, 106.816666], 13);
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(eventMap);
                    
                    mapMarker = L.marker([-6.200000, 106.816666], {draggable: true}).addTo(eventMap);
                    
                    // Update location input based on marker drag
                    mapMarker.on('dragend', function (e) {
                        const lat = mapMarker.getLatLng().lat.toFixed(6);
                        const lng = mapMarker.getLatLng().lng.toFixed(6);
                        document.getElementById('event-location-input').value = lat + ", " + lng;
                    });
                    
                    // Update marker on map click
                    eventMap.on('click', function(e) {
                        mapMarker.setLatLng(e.latlng);
                        const lat = e.latlng.lat.toFixed(6);
                        const lng = e.latlng.lng.toFixed(6);
                        document.getElementById('event-location-input').value = lat + ", " + lng;
                    });

                    // Try to get user's current location to set the map
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            eventMap.setView([lat, lng], 13);
                            mapMarker.setLatLng([lat, lng]);
                        });
                    }

                    mapInitialized = true;
                }, 100);
            }
        } else {
            container.style.display = 'none';
        }
    }

    function saveEvent() {
        const inputName = document.getElementById('event-name-input');
        const inputDate = document.getElementById('event-date-input');
        const inputTime = document.getElementById('event-time-input');
        const inputLoc = document.getElementById('event-location-input');
        
        const hiddenEvent = document.getElementById('post-event');
        const hiddenDate = document.getElementById('post-event-date');
        const hiddenTime = document.getElementById('post-event-time');
        const hiddenLoc = document.getElementById('post-event-location');
        
        const preview = document.getElementById('post-preview');
        const eventPreview = document.getElementById('event-preview');
        const eventText = document.getElementById('event-text');
        
        if (inputName.value.trim() !== '') {
            hiddenEvent.value = inputName.value;
            hiddenDate.value = inputDate.value;
            hiddenTime.value = inputTime.value;
            hiddenLoc.value = inputLoc.value;
            
            let displayTxt = "Acara: " + inputName.value;
            if (inputDate.value) displayTxt += " | " + inputDate.value;
            if (inputTime.value) displayTxt += " " + inputTime.value;
            if (inputLoc.value) displayTxt += " | 📍 " + inputLoc.value;
            
            eventText.textContent = displayTxt;
            eventPreview.style.display = 'flex';
            document.getElementById('event-input-container').style.display = 'none';
        }
    }

    function removeEvent() {
        document.getElementById('post-event').value = '';
        document.getElementById('post-event-date').value = '';
        document.getElementById('post-event-time').value = '';
        document.getElementById('post-event-location').value = '';
        
        document.getElementById('event-preview').style.display = 'none';
    }

    function toggleLocationInput() {
        toggleEventInput();
        setTimeout(() => {
            document.getElementById('event-location-input').focus();
        }, 100);
    }

    function togglePollInput() {
        const container = document.getElementById('poll-input-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            validatePostInput();
        } else {
            removePoll();
        }
    }

    function removePoll() {
        document.getElementById('poll-input-container').style.display = 'none';
        
        // Reset inputs
        const inputs = document.querySelectorAll('.poll-option-input');
        inputs.forEach((input, index) => {
            input.value = '';
            if (index > 1) {
                input.parentElement.remove();
            }
        });
        
        document.getElementById('add-poll-option-btn').style.display = 'block';
        validatePostInput();
    }

    function addPollOption() {
        const container = document.getElementById('poll-options-container');
        const count = container.children.length;
        
        if (count >= 4) return;
        
        const row = document.createElement('div');
        row.className = 'poll-option-row';
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.gap = '8px';
        
        row.innerHTML = `
            <input type="text" name="poll_options[]" placeholder="Pilihan ${count + 1}" class="poll-option-input" style="flex: 1; border: 1px solid var(--border-color); background: var(--bg-main); padding: 12px; border-radius: 8px; outline: none; color: var(--text-dark);" oninput="validatePostInput()">
            <button type="button" onclick="this.parentElement.remove(); document.getElementById('add-poll-option-btn').style.display = 'block'; validatePostInput();" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class='bx bx-trash' style="font-size: 20px;"></i></button>
        `;
        
        container.appendChild(row);
        
        if (count + 1 >= 4) {
            document.getElementById('add-poll-option-btn').style.display = 'none';
        }
        
        validatePostInput();
    }

    let feedSelectedPhotos = new DataTransfer();

    function removePhoto() {
        document.getElementById('post-photo').value = '';
        feedSelectedPhotos = new DataTransfer();
        document.getElementById('post-photo-slider').innerHTML = '';
        
        if (document.getElementById('event-preview').style.display === 'none' || document.getElementById('event-preview').style.display === '') {
            document.getElementById('post-preview').style.display = 'none';
        }
        validatePostInput();
    }

    let cropper = null;

    function openCropModal() {
        // Disabled for multi-photo support
    }

    function closeCropModal() {
        document.getElementById('crop-modal').style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function applyCrop() {
        // Disabled for multi-photo support
    }

    document.getElementById('post-photo')?.addEventListener('change', async function(e) {
        const input = e.target;
        const previewContainer = document.getElementById('post-preview');
        const slider = document.getElementById('post-photo-slider');

        if (input.files && input.files.length > 0) {
            const compressionOptions = {
                maxSizeMB: 1,
                maxWidthOrHeight: 1920,
                useWebWorker: true
            };

            for(let i=0; i < input.files.length; i++) {
                if(feedSelectedPhotos.items.length < 10) {
                    let file = input.files[i];
                    if (file.type.startsWith('image/')) {
                        try {
                            const compressedBlob = await browserImageCompression(file, compressionOptions);
                            
                            // Determine correct extension from blob type
                            let ext = 'jpg';
                            if (compressedBlob.type === 'image/png') ext = 'png';
                            else if (compressedBlob.type === 'image/webp') ext = 'webp';
                            
                            // Replace extension in filename if needed
                            let newName = file.name.replace(/\.[^/.]+$/, "") + '.' + ext;
                            
                            file = new File([compressedBlob], newName, {
                                type: compressedBlob.type,
                                lastModified: Date.now()
                            });
                        } catch (error) {
                            console.error('Image compression failed:', error);
                        }
                    }
                    feedSelectedPhotos.items.add(file);
                }
            }
            
                input.files = feedSelectedPhotos.files;
            slider.innerHTML = '';
            
            Array.from(input.files).forEach(file => {
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
                    video.style.maxHeight = '400px';
                    video.style.objectFit = 'cover';
                    video.style.borderRadius = '16px';
                    video.style.display = 'block';
                    video.style.border = '1px solid var(--border-color)';
                    imgWrapper.appendChild(video);
                } else {
                    const img = document.createElement('img');
                    img.src = objectUrl;
                    img.style.width = '100%';
                    img.style.maxHeight = '400px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '16px';
                    img.style.display = 'block';
                    img.style.border = '1px solid var(--border-color)';
                    imgWrapper.appendChild(img);
                }

                slider.appendChild(imgWrapper);
            });
            
            previewContainer.style.display = 'block';
            validatePostInput();
        }
    });

    function toggleLike(postId) {
        fetch(`/desktop/post/${postId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const btn = document.getElementById(`like-btn-${postId}`);
            const icon = document.getElementById(`like-icon-${postId}`);
            const count = document.getElementById(`like-count-${postId}`);
            
            count.innerText = data.likes_count;
            
            if (data.liked) {
                btn.classList.add('active');
                icon.classList.remove('bx-heart');
                icon.classList.add('bxs-heart');
                icon.style.color = 'var(--danger)'; // red heart
            } else {
                btn.classList.remove('active');
                icon.classList.remove('bxs-heart');
                icon.classList.add('bx-heart');
                icon.style.color = ''; // reset color
            }
        });
    }

    function votePoll(pollId, optionId) {
        fetch(`/desktop/poll/${pollId}/vote`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ option_id: optionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                Swal.fire({
                    title: 'Gagal',
                    text: data.error,
                    icon: 'error',
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                return;
            }
            
            // Re-render poll UI with results
            const container = document.getElementById(`poll-container-${pollId}`);
            let html = '';
            
            data.options.forEach(opt => {
                html += `
                <div style="margin-bottom: 12px; position: relative;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; position: relative; z-index: 2; padding: 0 8px;">
                        <span style="font-weight: 600; font-size: 14px; color: var(--text-dark);">${opt.text}</span>
                        <span style="font-weight: 700; font-size: 14px; color: var(--text-dark);">${opt.percent}%</span>
                    </div>
                    <div style="height: 36px; background: var(--bg-main); border-radius: 8px; overflow: hidden; position: relative; border: 1px solid var(--border-color);">
                        <div style="height: 100%; width: ${opt.percent}%; background: rgba(29, 155, 240, 0.2); transition: width 0.5s ease-in-out;"></div>
                    </div>
                </div>
                `;
            });
            
            html += `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                <span>${data.total_votes} suara</span>
                <span>Sisa waktu: ...</span>
            </div>
            `; // Note: Time remaining is simplified here on client side update
            
            container.innerHTML = html;
        });
    }

    function toggleComments(postId) {
        const commentsDiv = document.getElementById(`comments-${postId}`);
        if (commentsDiv.style.display === 'none') {
            commentsDiv.style.display = 'block';
        } else {
            commentsDiv.style.display = 'none';
        }
    }

    function sharePost(postId) {
        const url = window.location.origin + `/desktop/feed`; // Since we don't have individual post page yet
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Link feed berhasil disalin!',
                icon: 'success',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000
            });
        });
    }

    function toggleBookmark(postId) {
        fetch(`/desktop/post/${postId}/bookmark`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const btn = document.getElementById(`bookmark-btn-${postId}`);
            const icon = document.getElementById(`bookmark-icon-${postId}`);
            
            if (data.bookmarked) {
                btn.classList.add('active');
                icon.classList.remove('bx-bookmark');
                icon.classList.add('bxs-bookmark');
                icon.style.color = 'var(--primary)';
            } else {
                btn.classList.remove('active');
                icon.classList.remove('bxs-bookmark');
                icon.classList.add('bx-bookmark');
                icon.style.color = '';
            }
        });
    }

    let nextPageUrl = '{{ $posts->nextPageUrl() }}';
    let isLoading = false;

    window.addEventListener('scroll', () => {
        if (isLoading || !nextPageUrl) return;
        
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
            loadMorePosts();
        }
    });

    function loadMorePosts() {
        isLoading = true;
        document.getElementById('loading-indicator').style.display = 'block';
        
        fetch(nextPageUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('posts-container');
            container.insertAdjacentHTML('beforeend', data.html);
            nextPageUrl = data.next_page;
            
            if (!nextPageUrl) {
                document.getElementById('loading-indicator').innerHTML = '<p style="text-align: center; color: var(--text-muted); font-size: 13px; padding: 20px;">Tidak ada postingan lagi.</p>';
            } else {
                document.getElementById('loading-indicator').style.display = 'none';
            }
            isLoading = false;
        })
        .catch(error => {
            console.error('Error fetching posts:', error);
            isLoading = false;
            document.getElementById('loading-indicator').style.display = 'none';
        });
    }

    // Ajax Form Submission with Progress
    const composeForm = document.getElementById('compose-post-form');
    if (composeForm) {
        composeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!submitPostBtn) return;
            
            // Check total file size before upload (to prevent 413 Payload Too Large)
            let totalSize = 0;
            if (feedSelectedPhotos && feedSelectedPhotos.files.length > 0) {
                for (let i = 0; i < feedSelectedPhotos.files.length; i++) {
                    totalSize += feedSelectedPhotos.files[i].size;
                }
            }
            
            // Limit to 100MB total (to match Laravel max:102400 rule)
            if (totalSize > 100 * 1024 * 1024) {
                Swal.fire({
                    title: 'File Terlalu Besar',
                    text: 'Maksimal 100MB. Silakan kompres video atau pilih file yang lebih kecil.',
                    icon: 'warning',
                    confirmButtonColor: '#1d9bf0'
                });
                submitPostBtn.disabled = false;
                submitPostBtn.style.opacity = '1';
                submitPostBtn.innerText = 'Post';
                return;
            }

            const formData = new FormData(this);
            // Replace photos with feedSelectedPhotos items
            formData.delete('photos[]');
            if (feedSelectedPhotos && feedSelectedPhotos.files.length > 0) {
                for (let i = 0; i < feedSelectedPhotos.files.length; i++) {
                    formData.append('photos[]', feedSelectedPhotos.files[i]);
                }
            }

            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const statusText = document.getElementById('upload-status-text');
            
            if (progressContainer) progressContainer.style.display = 'block';
            if (statusText) statusText.style.display = 'block';
            
            submitPostBtn.disabled = true;
            submitPostBtn.style.opacity = '0.5';
            submitPostBtn.innerText = 'Posting...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    if (progressBar) progressBar.style.width = percentComplete + '%';
                    if (statusText) statusText.innerText = 'Mengunggah... ' + percentComplete + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (progressBar) progressBar.style.width = '100%';
                    if (statusText) statusText.innerText = 'Selesai!';
                    
                    composeForm.reset();
                    if(typeof removePhoto === 'function') removePhoto();
                    if(postContent) {
                        postContent.value = '';
                        postContent.style.height = '';
                    }
                    
                    // Reset Event inputs
                    if (typeof removeEvent === 'function') removeEvent();
                    // Reset Poll inputs
                    if (typeof removePoll === 'function') removePoll();

                    validatePostInput();
                    
                    setTimeout(() => {
                        if (response.html) {
                            const container = document.getElementById('posts-container');
                            if (container) {
                                container.insertAdjacentHTML('afterbegin', response.html);
                                
                                // Scroll ke atas untuk melihat post baru
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                                
                                // Tampilkan notifikasi sukses
                                if (typeof showToast === 'function') {
                                    showToast('Postingan berhasil dikirim!', 'success');
                                }
                            } else {
                                window.location.reload();
                            }
                        } else {
                            window.location.reload();
                        }

                        if (progressContainer) progressContainer.style.display = 'none';
                        if (statusText) statusText.style.display = 'none';
                        
                        // Re-enable button
                        submitPostBtn.disabled = false;
                        submitPostBtn.style.opacity = '1';
                        submitPostBtn.innerText = 'Post';
                    }, 800);
                } else {
                    if (statusText) statusText.innerText = 'Gagal mengunggah!';
                    if (progressBar) {
                        progressBar.style.backgroundColor = '#f4212e';
                        progressBar.style.width = '100%';
                    }
                    console.error('Upload Error:', xhr.status, xhr.responseText);
                    
                    let errorMsg = 'Terjadi kesalahan saat mengunggah.';
                    if (xhr.status === 413) {
                        errorMsg = 'Gagal: File terlalu besar (melebihi batas server).';
                    } else if (xhr.status === 422) {
                        errorMsg = 'Gagal: Format file tidak didukung atau ukuran melebihi batas.';
                    }
                    
                    Swal.fire({
                        title: 'Upload Gagal',
                        text: errorMsg,
                        icon: 'error',
                        confirmButtonColor: '#f4212e'
                    });
                    
                    submitPostBtn.disabled = false;
                    submitPostBtn.style.opacity = '1';
                    submitPostBtn.innerText = 'Post';
                    
                    if (progressContainer) progressContainer.style.display = 'none';
                    if (statusText) statusText.style.display = 'none';
                    validatePostInput();
                }
            };
            
            xhr.onerror = function() {
                Swal.fire({
                    title: 'Koneksi Terputus',
                    text: 'Gagal terhubung ke server. Periksa koneksi internet Anda.',
                    icon: 'error',
                    confirmButtonColor: '#f4212e'
                });
                if (progressContainer) progressContainer.style.display = 'none';
                if (statusText) statusText.style.display = 'none';
                submitPostBtn.disabled = false;
                submitPostBtn.innerText = 'Post';
                validatePostInput();
            };

            xhr.send(formData);
        });
    }
</script>
