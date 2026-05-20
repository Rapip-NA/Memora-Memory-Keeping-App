    <!-- Chat Overlay -->
    <div id="chat-overlay" class="chat-overlay" onclick="closeChatPanel()"></div>

    <!-- Chat Panel -->
    <div id="chat-panel" class="chat-panel">
        <div class="chat-header">
            <h3>Pesan</h3>
            <button onclick="closeChatPanel()" class="close-chat-btn"><i class='bx bx-x'></i></button>
        </div>
        <div class="chat-search">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Cari pesan...">
            </div>
        </div>
        <div class="chat-list">
            <!-- Chat Item 1 -->
            <div class="chat-item unread">
                <div class="chat-avatar">
                    <img src="https://ui-avatars.com/api/?name=Budi+S&background=random" alt="Budi">
                    <div class="status-indicator online"></div>
                </div>
                <div class="chat-info">
                    <div class="chat-name-time">
                        <h4>Budi Santoso</h4>
                        <span>10:42 AM</span>
                    </div>
                    <p class="chat-preview">Bro, besok jadi ketemuan di cafe deket kampus?</p>
                </div>
                <div class="unread-badge">2</div>
            </div>
            
            <!-- Chat Item 2 -->
            <div class="chat-item unread">
                <div class="chat-avatar">
                    <img src="https://ui-avatars.com/api/?name=Siska+M&background=random" alt="Siska">
                    <div class="status-indicator offline"></div>
                </div>
                <div class="chat-info">
                    <div class="chat-name-time">
                        <h4>Siska Meliana</h4>
                        <span>09:15 AM</span>
                    </div>
                    <p class="chat-preview">Jangan lupa kirim foto dokumentasi yang kemarin ya!</p>
                </div>
                <div class="unread-badge">1</div>
            </div>

            <!-- Chat Item 3 -->
            <div class="chat-item">
                <div class="chat-avatar">
                    <img src="https://ui-avatars.com/api/?name=Andi+W&background=random" alt="Andi">
                    <div class="status-indicator online"></div>
                </div>
                <div class="chat-info">
                    <div class="chat-name-time">
                        <h4>Andi Wijaya</h4>
                        <span>Senin</span>
                    </div>
                    <p class="chat-preview">Info loker IT udah aku forward ke emailmu ya.</p>
                </div>
            </div>
        </div>
        <div class="chat-footer">
            <a href="#">Lihat Semua Pesan</a>
        </div>
    </div>
