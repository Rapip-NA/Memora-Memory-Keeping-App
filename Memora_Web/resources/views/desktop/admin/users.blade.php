@extends('layouts.desktop')

@section('content')
<style>
    .users-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        color: var(--text-dark);
    }
    
    .users-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .users-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .users-header p {
        color: var(--text-muted);
        margin: 0;
        font-size: 15px;
    }

    .filter-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: flex-end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 10px 16px;
        color: var(--text-dark);
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: all 0.2s;
        width: 100%;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
    }

    .btn-filter-group {
        display: flex;
        gap: 12px;
    }

    .btn-action {
        border: none;
        padding: 11px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        font-family: inherit;
        text-decoration: none;
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        flex: 1;
    }

    .btn-submit:hover {
        background: var(--primary-hover, #1a8cd8);
        transform: translateY(-2px);
    }

    .btn-reset {
        background: var(--bg-main);
        color: var(--text-dark);
        border: 1px solid var(--border-color);
    }

    .btn-reset:hover {
        background: var(--border-color);
    }
    
    .users-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .users-table th {
        background: var(--bg-main);
        padding: 16px 24px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .users-table td {
        padding: 16px 24px;
        font-size: 15px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .users-table tr:last-child td {
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

    .user-badge {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .user-badge-class {
        background: rgba(29, 155, 240, 0.1);
        color: var(--primary);
        border: 1px solid rgba(29, 155, 240, 0.2);
    }

    .user-badge-role-admin {
        background: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .user-badge-role-user {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .user-badge-status-active {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }

    .user-badge-status-pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .user-badge-status-inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 40px;
    }
    
    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(29, 155, 240, 0.1);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin: 0 auto 24px;
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

    .btn-add-user {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--primary);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-add-user:hover {
        background: var(--primary-hover, #1a8cd8);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(29, 155, 240, 0.3);
        color: white;
    }

    .btn-action-sm {
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 16px;
    }

    .btn-edit-sm {
        background: rgba(99, 102, 241, 0.1);
        color: #6366f1;
    }

    .btn-edit-sm:hover {
        background: #6366f1;
        color: white;
        transform: translateY(-1px);
    }

    .btn-delete-sm {
        background: rgba(255, 77, 79, 0.1);
        color: #ff4d4f;
    }

    .btn-delete-sm:hover {
        background: #ff4d4f;
        color: white;
        transform: translateY(-1px);
    }

    /* ── Modal ────────────────────────────────────────── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9998;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 32px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.2);
        animation: modalIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.92) translateY(20px); }
        to   { opacity: 1; transform: scale(1)   translateY(0); }
    }

    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-subtitle {
        font-size: 14px;
        color: var(--text-muted);
        margin: 0 0 24px 0;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid var(--border-color);
        border-radius: 12px;
        background: var(--bg-main);
        color: var(--text-dark);
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .form-input:focus {
        border-color: var(--primary);
    }

    .form-input.is-invalid {
        border-color: #ff4d4f;
    }

    .invalid-feedback {
        font-size: 12px;
        color: #ff4d4f;
        margin-top: 4px;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .btn-cancel {
        flex: 1;
        padding: 12px;
        border: 1.5px solid var(--border-color);
        border-radius: 12px;
        background: transparent;
        color: var(--text-muted);
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        border-color: var(--text-dark);
        color: var(--text-dark);
    }

    .btn-submit {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 12px;
        background: var(--primary);
        color: white;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-submit:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>

<div class="users-wrapper">
    <div class="users-header">
        <div>
            <h2><i class='bx bx-group' style="color: var(--primary);"></i> Daftar Anggota</h2>
            <p>Kelola dan pantau seluruh akun alumni dan administrator di platform ini.</p>
        </div>
        <button class="btn-add-user" onclick="openAddUserModal()">
            <i class='bx bx-plus-circle'></i> Tambah Anggota
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div id="flash-success" style="background: linear-gradient(135deg, #38cb89, #2ecc71); color: white; padding: 14px 20px; border-radius: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; font-weight: 600; box-shadow: 0 4px 16px rgba(56,203,137,0.25);">
        <span style="display:flex; align-items:center; gap:10px;"><i class='bx bx-check-circle' style="font-size:20px;"></i>{{ session('success') }}</span>
        <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div style="background: linear-gradient(135deg, #ff4d4f, #f54254); color: white; padding: 14px 20px; border-radius: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
        <span style="display:flex; align-items:center; gap:10px;"><i class='bx bx-error-circle' style="font-size:20px;"></i>{{ session('error') }}</span>
        <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div style="background: rgba(255,77,79,0.1); border: 1px solid #ff4d4f; color: #ff4d4f; padding: 14px 20px; border-radius: 14px; margin-bottom: 24px; font-weight: 600;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 8px;"><i class='bx bx-error'></i> Terdapat kesalahan:</div>
        <ul style="margin: 0; padding-left: 20px; font-weight: 400; font-size: 14px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Filter Card -->
    <div class="filter-card">
        <form action="{{ route('admin.users') }}" method="GET" class="filter-form">
            <div class="form-group" style="grid-column: span 2;">
                <label for="search">Cari Anggota</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama, email, atau username..." class="form-control">
            </div>

            <div class="form-group">
                <label for="classroom_id">Kelas</label>
                <select name="classroom_id" id="classroom_id" class="form-control">
                    <option value="">Semua Kelas</option>
                    @foreach($classrooms as $cls)
                        <option value="{{ $cls->id }}" {{ request('classroom_id') == $cls->id ? 'selected' : '' }}>
                            {{ $cls->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="role">Kategori User</label>
                <select name="role" id="role" class="form-control">
                    <option value="">Semua Kategori</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator (Admin)</option>
                    <option value="member" {{ request('role') === 'member' ? 'selected' : '' }}>Alumni (User)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Ditolak / Nonaktif</option>
                </select>
            </div>

            <div class="btn-filter-group">
                <button type="submit" class="btn-action btn-submit">
                    <i class='bx bx-filter-alt'></i> Filter
                </button>
                @if(request()->anyFilled(['search', 'classroom_id', 'role', 'status']))
                    <a href="{{ route('admin.users') }}" class="btn-action btn-reset">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>
    
    <!-- Table Card -->
    <div class="users-card">
        @if($users->count() > 0)
        <div style="overflow-x: auto;">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Alumni</th>
                        <th>Kategori Kelas</th>
                        <th>Kategori User</th>
                        <th>Status</th>
                        <th>Tanggal Terdaftar</th>
                        <th style="text-align: right;">Aksi</th>
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
                        <td>
                            @if($user->classroom)
                                <span class="user-badge user-badge-class">
                                    <i class='bx bx-chalkboard'></i> {{ $user->classroom->name }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">Tidak Ada Kelas</span>
                            @endif
                        </td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="user-badge user-badge-role-admin">
                                    <i class='bx bx-shield-quarter'></i> Admin
                                </span>
                            @else
                                <span class="user-badge user-badge-role-user">
                                    <i class='bx bx-user'></i> Alumni
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="user-badge user-badge-status-active">
                                    <i class='bx bx-check-circle'></i> Aktif
                                </span>
                            @elseif($user->status === 'pending')
                                <span class="user-badge user-badge-status-pending">
                                    <i class='bx bx-time'></i> Menunggu
                                </span>
                            @else
                                <span class="user-badge user-badge-status-inactive">
                                    <i class='bx bx-block'></i> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td style="color: var(--text-muted); font-size: 14px;">
                            {{ $user->created_at->translatedFormat('d M Y') }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <button class="btn-action-sm btn-edit-sm"
                                    onclick="openEditUserModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ $user->classroom_id }}', '{{ $user->role }}', '{{ $user->status }}')">
                                    <i class='bx bx-edit-alt'></i>
                                </button>
                                @if(auth()->id() !== $user->id)
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                    id="form-delete-user-{{ $user->id }}" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn-action-sm btn-delete-sm"
                                        onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
        <div class="pagination-container">
            <div style="color: var(--text-muted); font-size: 14px;">
                Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} anggota
            </div>
            <div>
                {{ $users->links('vendor.pagination.custom') }}
            </div>
        </div>
        @endif
        
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class='bx bx-user-x'></i>
            </div>
            <h3>Anggota Tidak Ditemukan</h3>
            <p>Tidak ada data anggota yang cocok dengan kriteria filter Anda.</p>
        </div>
        @endif
    </div>
</div>

{{-- ── Modal Tambah Anggota ────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-add-user" onclick="handleOverlayClick(event, 'modal-add-user')">
    <div class="modal-box" style="max-width: 520px;">
        <h3 class="modal-title"><i class='bx bx-user-plus' style="color: var(--primary);"></i> Tambah Anggota Baru</h3>
        <p class="modal-subtitle">Tambahkan akun alumni atau administrator baru secara manual.</p>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="add-name">Nama Lengkap</label>
                <input type="text" id="add-name" name="name" class="form-input" placeholder="Contoh: John Doe" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="add-email">Alamat Email</label>
                <input type="email" id="add-email" name="email" class="form-input" placeholder="Contoh: johndoe@gmail.com" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="add-password">Kata Sandi</label>
                <input type="password" id="add-password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="add-classroom_id">Kelas</label>
                    <select id="add-classroom_id" name="classroom_id" class="form-input" required>
                        <option value="" disabled selected>Pilih Kelas</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-role">Kategori / Role</label>
                    <select id="add-role" name="role" class="form-input" required>
                        <option value="member">Alumni</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="add-status">Status Akun</label>
                <select id="add-status" name="status" class="form-input" required>
                    <option value="active">Aktif</option>
                    <option value="pending">Menunggu Validasi</option>
                    <option value="inactive">Nonaktif / Ditolak</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-add-user')">Batal</button>
                <button type="submit" class="btn-submit"><i class='bx bx-save'></i> Simpan Anggota</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Edit Anggota ──────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-edit-user" onclick="handleOverlayClick(event, 'modal-edit-user')">
    <div class="modal-box" style="max-width: 520px;">
        <h3 class="modal-title"><i class='bx bx-edit-alt' style="color: #6366f1;"></i> Edit Data Anggota</h3>
        <p class="modal-subtitle">Perbarui data profil dan pengaturan akun anggota.</p>
        <form id="form-edit-user" action="" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="edit-name">Nama Lengkap</label>
                <input type="text" id="edit-name" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit-email">Alamat Email</label>
                <input type="email" id="edit-email" name="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit-password">Kata Sandi Baru <span style="font-weight:400; text-transform:none;">(kosongkan jika tidak diubah)</span></label>
                <input type="password" id="edit-password" name="password" class="form-input" placeholder="Minimal 8 karakter">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="edit-classroom_id">Kelas</label>
                    <select id="edit-classroom_id" name="classroom_id" class="form-input" required>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit-role">Kategori / Role</label>
                    <select id="edit-role" name="role" class="form-input" required>
                        <option value="member">Alumni</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit-status">Status Akun</label>
                <select id="edit-status" name="status" class="form-input" required>
                    <option value="active">Aktif</option>
                    <option value="pending">Menunggu Validasi</option>
                    <option value="inactive">Nonaktif / Ditolak</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-edit-user')">Batal</button>
                <button type="submit" class="btn-submit"><i class='bx bx-save'></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Modal Helpers ──────────────────────────────────────────
    function openAddUserModal() {
        document.getElementById('modal-add-user').classList.add('active');
        setTimeout(() => document.getElementById('add-name').focus(), 100);
    }

    function openEditUserModal(id, name, email, classroomId, role, status) {
        const form = document.getElementById('form-edit-user');
        form.action = `/admin/users/${id}`;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-classroom_id').value = classroomId;
        document.getElementById('edit-role').value = role;
        document.getElementById('edit-status').value = status;
        document.getElementById('modal-edit-user').classList.add('active');
        setTimeout(() => document.getElementById('edit-name').focus(), 100);
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function handleOverlayClick(event, id) {
        if (event.target === document.getElementById(id)) closeModal(id);
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal('modal-add-user');
            closeModal('modal-edit-user');
        }
    });

    // Auto-open add modal if validation errors exist on store
    @if($errors->any() && old('name') !== null && !session()->has('errors_for_edit'))
        openAddUserModal();
    @endif

    // ── Delete Confirm ─────────────────────────────────────────
    function confirmDeleteUser(id, name) {
        Swal.fire({
            title: `Hapus Anggota "${name}"?`,
            text: `Akun anggota "${name}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: 'var(--border-color)',
            confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: { popup: 'swal-premium-popup' }
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById(`form-delete-user-${id}`).submit();
            }
        });
    }

    // Auto-dismiss flash after 4s
    setTimeout(() => {
        const el = document.getElementById('flash-success');
        if (el) { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(() => el.remove(), 500); }
    }, 4000);
</script>
@endsection
