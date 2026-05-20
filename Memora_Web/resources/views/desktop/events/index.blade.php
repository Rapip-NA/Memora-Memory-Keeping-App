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
    document.addEventListener('DOMContentLoaded', function () {
        // Auto dismiss toast
        const toast = document.getElementById('rsvp-toast');
        if (toast) {
            setTimeout(() => {
                toast.style.transition = 'opacity 0.4s, transform 0.4s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(40px)';
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        // Loading state for all RSVP forms
        document.querySelectorAll('form[action*="rsvp"]').forEach(form => {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = "<i class='bx bx-loader-alt' style='font-size:17px;animation:spin 0.8s linear infinite'></i> Menyimpan...";
                }
            });
        });
    });
</script>

<div class="content-wrapper" style="padding: 24px; width: 100%; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Events & Gatherings</h1>
            <p style="color: var(--text-muted); font-size: 15px; margin: 0;">Jelajahi acara mendatang dan kenangan reuni kita.</p>
        </div>
        <button class="primary-btn" onclick="openGlobalComposeModal()" style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
            <i class='bx bx-plus' style="font-size: 20px;"></i> Buat Acara
        </button>
    </div>

    <!-- Upcoming Events Section -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bxs-calendar-star' style="color: var(--primary);"></i> Acara Mendatang
        </h2>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            @forelse($upcomingEvents as $event)
            <div class="ev-card">
                <div class="ev-card__inner">
                    <div class="ev-date">
                        <div class="ev-date__month">{{ $event->event_date->translatedFormat('M') }}</div>
                        <div class="ev-date__day">{{ $event->event_date->format('d') }}</div>
                    </div>
                    <div class="ev-meta">
                        <h3 class="ev-meta__title">{{ $event->title }}</h3>
                        <div class="ev-meta__info">
                            <span class="ev-meta__chip"><i class='bx bx-time-five'></i> {{ $event->event_date->format('H:i') }} WIB</span>
                            <span class="ev-meta__chip"><i class='bx bx-map'></i> {{ $event->location }}</span>
                        </div>
                    </div>
                </div>

                @if($event->clean_description)
                <p class="ev-desc">{{ $event->clean_description }}</p>
                @endif

                <div class="ev-actions">
                    <form action="{{ route('desktop.events.rsvp', $event->id) }}" method="POST" style="flex: 1;">
                        @csrf
                        @php
                            $hasRsvped = $event->rsvps->contains('user_id', $currentUser->id);
                        @endphp
                        <button type="submit" class="rsvp-btn {{ $hasRsvped ? 'rsvp-btn--active' : 'rsvp-btn--default' }}">
                            @if($hasRsvped)
                                <i class='bx bxs-check-circle rsvp-btn__check' style="font-size: 17px;"></i> Hadir
                            @else
                                <i class='bx bx-check-circle' style="font-size: 17px;"></i> RSVP
                            @endif
                        </button>
                    </form>
                    <a href="{{ route('desktop.events.show', $event->id) }}" class="detail-link">
                        Detail <i class='bx bx-chevron-right'></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="ev-empty">
                <div class="ev-empty__icon">
                    <i class='bx bx-calendar-x'></i>
                </div>
                <div class="ev-empty__title">Belum Ada Acara</div>
                <p class="ev-empty__text">Belum ada acara mendatang yang dijadwalkan. Jadilah yang pertama membuat acara!</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Past Events Section -->
    <div>
        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-history' style="color: var(--text-muted);"></i> Acara Terdahulu
        </h2>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
            @forelse($pastEvents as $event)
            <div class="ev-past">
                <div class="ev-past__date">
                    <span class="ev-past__month">{{ $event->event_date->translatedFormat('M') }}</span>
                    <span class="ev-past__day">{{ $event->event_date->format('d') }}</span>
                    <span class="ev-past__year">{{ $event->event_date->format('Y') }}</span>
                </div>
                <div class="ev-past__info">
                    <h4 class="ev-past__title">{{ $event->title }}</h4>
                    <p class="ev-past__loc"><i class='bx bx-map'></i> {{ $event->location }}</p>
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; color: var(--text-muted); font-size: 14px; text-align: center; padding: 20px;">
                Belum ada history acara.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
