@extends('layouts.desktop')

@section('content')
<style>
    .validation-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        color: var(--text-dark);
    }
    
    .validation-header {
        margin-bottom: 32px;
    }
    
    .validation-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .validation-header p {
        color: var(--text-muted);
        margin: 0;
        font-size: 15px;
    }
    
    .validation-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    
    .validation-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .validation-table th {
        background: var(--bg-main);
        padding: 16px 24px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .validation-table td {
        padding: 20px 24px;
        font-size: 15px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .validation-table tr:last-child td {
        border-bottom: none;
    }
    
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--border-color);
        object-fit: cover;
        border: 1px solid var(--border-color);
    }
    
    .user-details h4 {
        margin: 0;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .user-details p {
        margin: 2px 0 0 0;
        font-size: 13px;
        color: var(--text-muted);
    }

    .badge-city {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .action-btn-group {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        border: none;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: inherit;
    }
    
    .btn-approve {
        background: rgba(56, 203, 137, 0.1);
        color: #38cb89;
    }
    
    .btn-approve:hover {
        background: #38cb89;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(56, 203, 137, 0.25);
    }
    
    .btn-reject {
        background: rgba(255, 77, 79, 0.1);
        color: #ff4d4f;
    }
    
    .btn-reject:hover {
        background: #ff4d4f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 77, 79, 0.25);
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 40px;
    }
    
    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(56, 203, 137, 0.1);
        color: #38cb89;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin: 0 auto 24px;
        animation: pulseGreen 2s infinite;
    }
    
    @keyframes pulseGreen {
        0%, 100% { box-shadow: 0 0 0 0 rgba(56, 203, 137, 0.2); }
        50% { box-shadow: 0 0 0 16px rgba(56, 203, 137, 0); }
    }
    
    .empty-state h3 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .empty-state p {
        margin: 0;
        color: var(--text-muted);
        font-size: 15px;
    }
    
    .pagination-container {
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="validation-wrapper">
    <div class="validation-header">
        <h2><i class='bx bx-user-check' style="color: var(--primary);"></i> Validasi Anggota Baru</h2>
        <p>Tinjau dan setujui registrasi alumni baru demi keamanan dan eksklusivitas komunitas.</p>
    </div>
    
    @if(session('success'))
    <div style="background: #38cb89; color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div style="background: #ff4d4f; color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
        <span>{{ session('error') }}</span>
        <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
    </div>
    @endif
    
    <div class="validation-card">
        @if($users->count() > 0)
        <div style="overflow-x: auto;">
            <table class="validation-table">
                <thead>
                    <tr>
                        <th>Alumni</th>
                        <th>Tanggal Daftar</th>
                        <th>Kota</th>
                        <th style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="user-info-cell">
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="user-avatar">
                                <div class="user-details">
                                    <h4>{{ $user->name }}</h4>
                                    <p>{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td style="color: var(--text-muted); font-size: 14px;">
                            {{ $user->created_at->translatedFormat('d F Y, H:i') }} WIB
                        </td>
                        <td>
                            <span class="badge-city">
                                <i class='bx bx-map'></i> {{ $user->city ?: 'Teyvat' }}
                            </span>
                        </td>
                        <td>
                            <div class="action-btn-group">
                                <form action="{{ route('admin.validation.approve', $user->id) }}" method="POST" id="form-approve-{{ $user->id }}" style="margin: 0;">
                                    @csrf
                                    <button type="button" onclick="confirmApprove({{ $user->id }}, '{{ $user->name }}')" class="action-btn btn-approve">
                                        <i class='bx bx-check-circle'></i> Setuju
                                    </button>
                                </form>
                                <form action="{{ route('admin.validation.reject', $user->id) }}" method="POST" id="form-reject-{{ $user->id }}" style="margin: 0;">
                                    @csrf
                                    <button type="button" onclick="confirmReject({{ $user->id }}, '{{ $user->name }}')" class="action-btn btn-reject">
                                        <i class='bx bx-x-circle'></i> Tolak
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="pagination-container">
            {{ $users->links() }}
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class='bx bx-party'></i>
            </div>
            <h3>Semua Pendaftaran Tervalidasi!</h3>
            <p>Hebat! Tidak ada pendaftaran tertunda saat ini. Angkatan alumni bersih dari antrean.</p>
        </div>
        @endif
    </div>
</div>

<script>
    function confirmApprove(userId, userName) {
        Swal.fire({
            title: 'Setujui Registrasi?',
            text: `Apakah Anda yakin ingin memberikan akses penuh kepada ${userName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#38cb89',
            cancelButtonColor: 'var(--border-color)',
            confirmButtonText: '<i class="bx bx-check"></i> Ya, Setujui',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal-premium-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`form-approve-${userId}`).submit();
            }
        });
    }

    function confirmReject(userId, userName) {
        Swal.fire({
            title: 'Tolak Registrasi?',
            text: `Apakah Anda yakin ingin menolak registrasi ${userName}? Akun mereka akan ditandai tidak aktif.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: 'var(--border-color)',
            confirmButtonText: '<i class="bx bx-x"></i> Ya, Tolak',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal-premium-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`form-reject-${userId}`).submit();
            }
        });
    }
</script>
@endsection
