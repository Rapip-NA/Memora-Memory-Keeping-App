@extends('layouts.desktop')

@section('content')

{{-- Toast Notification --}}
@if(session('success'))
<div id="rsvp-toast" style="
    position: fixed; top: 24px; right: 24px; z-index: 9999;
    background: #22c55e; color: #fff;
    padding: 14px 20px; border-radius: 14px;
    font-size: 14px; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 30px rgba(34,197,94,0.35);
    animation: slideInToast 0.35s cubic-bezier(.4,0,.2,1);
">
    <i class='bx bxs-check-circle' style="font-size: 20px;"></i>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div id="rsvp-toast" style="
    position: fixed; top: 24px; right: 24px; z-index: 9999;
    background: #ef4444; color: #fff;
    padding: 14px 20px; border-radius: 14px;
    font-size: 14px; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 30px rgba(239,68,68,0.35);
    animation: slideInToast 0.35s cubic-bezier(.4,0,.2,1);
">
    <i class='bx bxs-error-circle' style="font-size: 20px;"></i>
    {{ session('error') }}
</div>
@endif

<style>
@keyframes slideInToast {
    from { opacity: 0; transform: translateX(40px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>

<script>
    // Auto dismiss toast after 3 seconds
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('rsvp-toast');
        if (toast) {
            setTimeout(() => {
                toast.style.transition = 'opacity 0.4s, transform 0.4s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(40px)';
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        // Loading state for RSVP form
        document.querySelectorAll('form[action*="rsvp"]').forEach(form => {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = "<i class='bx bx-loader-alt' style='font-size:20px;animation:spin 0.8s linear infinite'></i> Menyimpan...";
                }
            });
        });
    });
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="content-wrapper" style="padding: 24px; width: 100%; margin: 0 auto; max-width: 1200px;">
    <!-- Top Navigation -->
    <div style="margin-bottom: 24px;">
        <a href="{{ route('desktop.events') }}" style="color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
            <i class='bx bx-arrow-back'></i> Kembali ke Events
        </a>
    </div>

    <!-- Main Bento Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Left Column (Main Info, Comments) -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Hero / Title Card -->
            <div style="background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color); padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div style="display: flex; gap: 24px; align-items: flex-start; margin-bottom: 24px;">
                    <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 16px; min-width: 80px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                        <div style="background: #ef4444; color: white; text-align: center; font-size: 13px; font-weight: 700; padding: 6px; text-transform: uppercase; letter-spacing: 1px;">
                            {{ $event->event_date->translatedFormat('M') }}
                        </div>
                        <div style="text-align: center; padding: 12px 8px; font-size: 32px; font-weight: 800; color: var(--text-dark); line-height: 1;">
                            {{ $event->event_date->format('d') }}
                        </div>
                        <div style="text-align: center; padding-bottom: 8px; font-size: 12px; font-weight: 600; color: var(--text-muted);">
                            {{ $event->event_date->format('Y') }}
                        </div>
                    </div>
                    <div>
                        <h1 style="font-size: 28px; font-weight: 800; color: var(--text-dark); margin: 0 0 12px 0; line-height: 1.2;">{{ $event->title }}</h1>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; color: var(--text-muted); font-size: 14px; font-weight: 500;">
                            <div style="display: flex; align-items: center; gap: 6px; background: var(--bg-main); padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border-color);">
                                <i class='bx bx-time-five' style="color: var(--primary);"></i> {{ $event->event_date->format('H:i') }} WIB
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; background: var(--bg-main); padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border-color);">
                                <i class='bx bx-map' style="color: var(--primary);"></i> {{ $event->location }}
                            </div>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 24px;">
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;">Deskripsi Acara</h3>
                    <div style="font-size: 15px; color: var(--text-light); line-height: 1.6; white-space: pre-wrap;">{{ $event->clean_description ?: 'Tidak ada deskripsi tambahan.' }}</div>
                </div>
            </div>

            <!-- Comments / Discussion -->
            <div style="background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color); padding: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-message-square-dots' style="color: var(--primary);"></i> Diskusi Persiapan ({{ $event->comments->count() }})
                </h3>

                <!-- Comment Form -->
                <form action="{{ route('desktop.events.comment', $event->id) }}" method="POST" style="display: flex; gap: 12px; margin-bottom: 32px;">
                    @csrf
                    <img src="{{ $currentUser->photo ? asset('storage/' . $currentUser->photo) : asset('images/default-avatar.png') }}" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        <textarea name="body" rows="2" placeholder="Tulis sesuatu... (Misal: kumpul dimana dulu?)" style="width: 100%; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; font-size: 14px; color: var(--text-dark); resize: vertical; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-color)'" required></textarea>
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="primary-btn" style="padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 13px;">Kirim Komentar</button>
                        </div>
                    </div>
                </form>

                <!-- Comments List -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    @forelse($event->comments->sortByDesc('created_at') as $comment)
                    <div style="display: flex; gap: 12px;">
                        <img src="{{ $comment->user->photo ? asset('storage/' . $comment->user->photo) : asset('images/default-avatar.png') }}" alt="User" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <div style="flex: 1;">
                            <div style="background: var(--bg-main); padding: 12px 16px; border-radius: 0 16px 16px 16px; border: 1px solid var(--border-color);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="font-weight: 700; font-size: 14px; color: var(--text-dark);">{{ $comment->user->name }}</span>
                                    <span style="font-size: 12px; color: var(--text-muted);">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <div style="font-size: 14px; color: var(--text-light); line-height: 1.5; white-space: pre-wrap;">{{ $comment->body }}</div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div style="text-align: center; color: var(--text-muted); font-size: 14px; padding: 20px;">
                        Belum ada diskusi. Mulai percakapan sekarang!
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column (Map, RSVP, Gallery Placeholder) -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- RSVP Box -->
            <div class="rsvp-box">
                <div class="rsvp-box__header">
                    <div class="rsvp-box__title">
                        <i class='bx bx-calendar-check'></i> RSVP
                    </div>
                    <div class="rsvp-box__count">
                        <i class='bx bxs-user-check'></i> {{ $event->rsvps->count() }} Hadir
                    </div>
                </div>

                <!-- RSVP Faces -->
                <div class="rsvp-faces">
                    @forelse($event->rsvps->take(8) as $rsvp)
                    <img src="{{ $rsvp->user->photo ? asset('storage/' . $rsvp->user->photo) : asset('images/default-avatar.png') }}" 
                         alt="{{ $rsvp->user->name }}" 
                         class="rsvp-faces__avatar" 
                         title="{{ $rsvp->user->name }}">
                    @empty
                    <div class="rsvp-faces__empty">
                        <i class='bx bx-ghost'></i> Belum ada yang RSVP. Jadilah yang pertama!
                    </div>
                    @endforelse

                    @if($event->rsvps->count() > 8)
                    <div class="rsvp-faces__more">
                        +{{ $event->rsvps->count() - 8 }}
                    </div>
                    @endif
                </div>

                <form action="{{ route('desktop.events.rsvp', $event->id) }}" method="POST">
                    @csrf
                    @php
                        $hasRsvped = $event->rsvps->contains('user_id', $currentUser->id);
                    @endphp
                    <button type="submit" class="rsvp-btn-full {{ $hasRsvped ? 'rsvp-btn-full--active' : 'rsvp-btn-full--default' }}">
                        @if($hasRsvped)
                            <i class='bx bxs-check-circle rsvp-btn__check' style="font-size: 20px;"></i> Batalkan RSVP
                        @else
                            <i class='bx bx-check-circle' style="font-size: 20px;"></i> Saya Akan Hadir
                        @endif
                    </button>
                </form>
            </div>

            <!-- Map Box -->
            <div style="background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-soft);">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-map-alt' style="color: var(--primary);"></i> Peta Lokasi
                </h3>
                <div style="width: 100%; height: 200px; border-radius: 16px; overflow: hidden; background: var(--bg-main); border: 1px solid var(--border-color);">
                    <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q={{ urlencode($event->location) }}&t=&z=15&ie=UTF8&iwloc=&output=embed" style="filter: var(--map-filter, none);"></iframe>
                </div>
                <div style="margin-top: 12px; font-size: 13px; color: var(--text-muted); text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class='bx bx-map'></i> {{ $event->location }}
                </div>
            </div>

            <!-- Gallery Placeholder Box -->
            <div style="background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-soft);">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-photo-album' style="color: var(--primary);"></i> Galeri Event
                </h3>
                
                @if($event->event_date->isPast())
                <div style="background: var(--bg-main); border: 1px dashed var(--border-color); border-radius: 16px; padding: 32px 16px; text-align: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='var(--border-color)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='var(--bg-main)'; this.style.borderColor='var(--border-color)';" onclick="window.location.href='{{ route('desktop.gallery', ['album' => $event->title]) }}'">
                    <i class='bx bx-cloud-upload' style="font-size: 32px; color: var(--primary); margin-bottom: 8px;"></i>
                    <div style="font-size: 14px; font-weight: 600; color: var(--text-dark);">Lihat/Upload Foto Acara</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Simpan kenangan dari acara ini ke galeri</div>
                </div>
                @else
                <div style="position: relative; overflow: hidden; border-radius: 16px; border: 1px solid var(--border-color); padding: 40px 20px; text-align: center; background: linear-gradient(145deg, var(--bg-main), var(--bg-card));">
                    <div style="position: absolute; top: -20px; right: -20px; font-size: 120px; color: var(--text-muted); opacity: 0.05; transform: rotate(15deg);">
                        <i class='bx bx-lock'></i>
                    </div>
                    <div style="width: 56px; height: 56px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; backdrop-filter: blur(4px);">
                        <i class='bx bx-lock-alt' style="font-size: 24px; color: var(--text-light);"></i>
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--text-dark);">Galeri Terkunci</div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 6px; line-height: 1.6; max-width: 220px; margin-left: auto; margin-right: auto;">
                        Galeri akan otomatis terbuka setelah acara selesai untuk dokumentasi.
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
