<div class="widgets-column" style="display: flex; flex-direction: column; gap: 20px; position: sticky; top: 24px; height: calc(100vh - 48px); overflow-y: auto; scrollbar-width: none; padding-bottom: 24px;">
    <style>
        .widgets-column::-webkit-scrollbar { display: none; }
    </style>
    
    @php
        $activeUsers = \App\Models\User::inRandomOrder()->limit(8)->get();
        $upcomingEvents = \App\Models\Event::whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date', 'asc')->limit(2)->get();
        $throwbackPhoto = \App\Models\GalleryPhoto::with('user')->inRandomOrder()->first();
    @endphp

    <!-- Search Bar in Sidebar -->
    <form action="{{ route('desktop.explore') }}" method="GET" class="search-bar" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-full); padding: 12px 16px; display: flex; align-items: center;">
        <i class='bx bx-search' style="font-size: 20px; color: var(--text-muted); margin-right: 8px;"></i>
        <input type="text" name="q" placeholder="Search for alumni, events..." value="{{ request('q') }}" style="border: none; background: transparent; outline: none; width: 100%; color: var(--text-primary); font-family: inherit;">
    </form>

    <!-- Active Now (Siapa yang Online) -->
    <div class="widget-card">
        <div class="widget-head" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-broadcast' style="color: #10b981; font-size: 18px;"></i> Active Now
            </h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; justify-items: center;">
            @php
                // Ambil maksimal 5 atau 10 user. Di sini kita batasi baris jadi 1 atau 2 baris (kelipatan 5)
                $totalSlots = 5;
                // Jika user aktif > 5, bisa ubah ke 10. Kita tetapkan 5 sesuai permintaan kolom 5
            @endphp
            @for($i = 0; $i < $totalSlots; $i++)
                @if(isset($activeUsers[$i]))
                    @php 
                        $aUser = $activeUsers[$i];
                        $aAvatar = $aUser->photo ? Storage::url($aUser->photo) : "https://ui-avatars.com/api/?name=".urlencode($aUser->name)."&background=random";
                    @endphp
                    <div style="position: relative; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="{{ $aUser->name }}">
                        <img src="{{ $aAvatar }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-card); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="position: absolute; bottom: 0px; right: -2px; width: 14px; height: 14px; background: #10b981; border-radius: 50%; border: 2px solid var(--bg-card);"></div>
                    </div>
                @else
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--bg-main); border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-light); box-sizing: border-box;" title="Slot Kosong">
                        <i class='bx bx-user' style="font-size: 20px; opacity: 0.5;"></i>
                    </div>
                @endif
            @endfor
        </div>
    </div>

    <!-- Upcoming Events (Event Card) -->
    <div class="widget-card" style="background: var(--bg-card); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
        <div class="widget-head" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600;">Acara Mendatang</h3>
            <a href="{{ route('desktop.events') }}" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 500;">Lihat</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            @forelse($upcomingEvents as $event)
            <div style="display: flex; gap: 14px; align-items: center;">
                <div style="background: var(--bg-main); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 12px; padding: 8px; text-align: center; min-width: 52px; height: 56px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px;">{{ $event->event_date->translatedFormat('M') }}</span>
                    <span style="font-size: 18px; font-weight: 800; line-height: 1;">{{ $event->event_date->format('d') }}</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h6 style="font-size: 15px; font-weight: 600; margin: 0; margin-bottom: 4px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $event->title }}</h6>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class='bx bx-map' style="vertical-align: middle;"></i> {{ $event->location }}</p>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--text-muted); font-size: 13px; padding: 20px; background: var(--bg-main); border-radius: 12px;">
                <i class='bx bx-calendar-x' style="font-size: 24px; color: var(--text-light); margin-bottom: 8px; display: block;"></i>
                Tidak ada event
            </div>
            @endforelse
        </div>
    </div>

    <!-- Throwback Corner (Memory Lane) -->
    <div class="widget-card" style="background: var(--bg-card); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
        <div class="widget-head" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-time-five' style="color: var(--primary); font-size: 18px;"></i> Throwback Corner
            </h3>
        </div>
        @if($throwbackPhoto)
        <div style="border-radius: 12px; overflow: hidden; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <img src="{{ Storage::url($throwbackPhoto->file_path) }}" alt="Throwback" style="width: 100%; height: 180px; object-fit: cover; display: block; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); padding: 24px 12px 12px; pointer-events: none;">
                <p style="color: white; font-size: 13px; font-weight: 600; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.8); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $throwbackPhoto->caption ?: 'Kenangan manis kita...' }}</p>
                <p style="color: rgba(255,255,255,0.8); font-size: 11px; margin: 4px 0 0;"><i class='bx bx-camera'></i> {{ $throwbackPhoto->user->name ?? 'Anonim' }}</p>
            </div>
        </div>
        @else
        <div style="text-align: center; color: var(--text-muted); font-size: 13px; padding: 20px; background: var(--bg-main); border-radius: 12px;">
            Belum ada foto kenangan. Unggah foto di galeri!
        </div>
        @endif
    </div>
    
    <!-- Footer Links -->
    <div style="padding: 0 8px; display: flex; flex-wrap: wrap; gap: 12px; font-size: 13px; color: var(--text-muted);">
        <a href="#" style="color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">Tentang</a>
        <a href="#" style="color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">Bantuan</a>
        <a href="#" style="color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">Privasi</a>
        <a href="#" style="color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">Ketentuan</a>
        <div style="width: 100%; margin-top: 4px; font-size: 12px;">© 2026 The Archive (memora)</div>
    </div>

</div>
