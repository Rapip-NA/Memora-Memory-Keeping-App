@extends('layouts.desktop')

@section('content')
<style>
    .classroom-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        color: var(--text-dark);
    }

    .classroom-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 24px;
        flex-wrap: wrap;
    }

    .classroom-title h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .classroom-title p {
        color: var(--text-muted);
        margin: 0;
        font-size: 15px;
    }

    .btn-add-classroom {
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

    .btn-add-classroom:hover {
        background: var(--primary-hover, #0d9f6e);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(var(--primary-rgb, 16, 185, 129), 0.3);
        color: white;
    }

    /* ── Grid Layout ─────────────────────────────────── */
    .classroom-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .classroom-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .classroom-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), #6366f1);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .classroom-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 32px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }

    .classroom-card:hover::before {
        opacity: 1;
    }

    .classroom-card-top {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .classroom-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(var(--primary-rgb, 16, 185, 129), 0.12), rgba(99, 102, 241, 0.12));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: var(--primary);
        flex-shrink: 0;
    }

    .classroom-info {
        flex: 1;
        min-width: 0;
    }

    .classroom-name {
        font-size: 17px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 4px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .classroom-desc {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .classroom-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .member-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        border-radius: 999px;
        color: var(--text-dark);
    }

    .member-badge i {
        font-size: 15px;
        color: var(--primary);
    }

    .classroom-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
    }

    .btn-action {
        flex: 1;
        border: none;
        padding: 9px 12px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: inherit;
    }

    .btn-edit {
        background: rgba(99, 102, 241, 0.1);
        color: #6366f1;
    }

    .btn-edit:hover {
        background: #6366f1;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .btn-delete {
        background: rgba(255, 77, 79, 0.1);
        color: #ff4d4f;
    }

    .btn-delete:hover {
        background: #ff4d4f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 77, 79, 0.25);
    }

    /* ── Stats Summary ────────────────────────────────── */
    .summary-bar {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 16px 24px;
        display: flex;
        gap: 32px;
        align-items: center;
        margin-bottom: 28px;
        flex-wrap: wrap;
    }

    .summary-stat {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .summary-stat .num {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1;
    }

    .summary-stat .lbl {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-divider {
        width: 1px;
        height: 36px;
        background: var(--border-color);
    }

    /* ── Empty State ──────────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 80px 40px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb, 16, 185, 129), 0.08);
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
        margin: 0 0 24px 0;
        color: var(--text-muted);
        font-size: 15px;
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

<div class="classroom-wrapper">

    {{-- Header --}}
    <div class="classroom-header">
        <div class="classroom-title">
            <h2><i class='bx bx-chalkboard' style="color: var(--primary);"></i> Manajemen Kelas</h2>
            <p>Kelola kategori kelas alumni. Kelas ini akan tersedia saat proses registrasi anggota baru.</p>
        </div>
        <button class="btn-add-classroom" onclick="openAddModal()">
            <i class='bx bx-plus-circle'></i> Tambah Kelas
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

    {{-- Summary Bar --}}
    @php
        $totalMembers = $classrooms->sum('users_count');
        $totalClasses = $classrooms->count();
        $emptyClasses = $classrooms->where('users_count', 0)->count();
    @endphp
    <div class="summary-bar">
        <div class="summary-stat">
            <span class="num">{{ $totalClasses }}</span>
            <span class="lbl">Total Kelas</span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-stat">
            <span class="num">{{ $totalMembers }}</span>
            <span class="lbl">Total Anggota Terdaftar</span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-stat">
            <span class="num">{{ $emptyClasses }}</span>
            <span class="lbl">Kelas Kosong</span>
        </div>
    </div>

    {{-- Grid Classroom Cards --}}
    @if($classrooms->count() > 0)
    <div class="classroom-grid">
        @foreach($classrooms as $classroom)
        <div class="classroom-card">
            <div class="classroom-card-top">
                <div class="classroom-icon">
                    <i class='bx bx-chalkboard'></i>
                </div>
                <div class="classroom-info">
                    <h3 class="classroom-name">{{ $classroom->name }}</h3>
                    <p class="classroom-desc">{{ $classroom->description ?: 'Tidak ada deskripsi.' }}</p>
                </div>
            </div>
            <div class="classroom-meta">
                <span class="member-badge">
                    <i class='bx bx-group'></i>
                    {{ $classroom->users_count }} Anggota
                </span>
                <span style="font-size:12px; color: var(--text-muted); margin-left: auto;">
                    {{ $classroom->created_at->format('d M Y') }}
                </span>
            </div>
            <div class="classroom-actions">
                <button class="btn-action btn-edit"
                    onclick="openEditModal({{ $classroom->id }}, '{{ addslashes($classroom->name) }}', '{{ addslashes($classroom->description ?? '') }}')">
                    <i class='bx bx-edit-alt'></i> Edit
                </button>
                <form action="{{ route('admin.classrooms.destroy', $classroom->id) }}" method="POST"
                    id="form-delete-classroom-{{ $classroom->id }}" style="flex:1; margin:0;">
                    @csrf @method('DELETE')
                    <button type="button" class="btn-action btn-delete" style="width:100%"
                        onclick="confirmDelete({{ $classroom->id }}, '{{ addslashes($classroom->name) }}', {{ $classroom->users_count }})">
                        <i class='bx bx-trash'></i> Hapus
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon"><i class='bx bx-chalkboard'></i></div>
        <h3>Belum Ada Kelas</h3>
        <p>Mulai tambahkan kategori kelas untuk membedakan angkatan alumni.</p>
        <button class="btn-add-classroom" onclick="openAddModal()">
            <i class='bx bx-plus-circle'></i> Tambah Kelas Pertama
        </button>
    </div>
    @endif

</div>

{{-- ── Modal Tambah Kelas ──────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-add" onclick="handleOverlayClick(event, 'modal-add')">
    <div class="modal-box">
        <h3 class="modal-title"><i class='bx bx-plus-circle' style="color: var(--primary);"></i> Tambah Kelas Baru</h3>
        <p class="modal-subtitle">Kelas ini akan tampil sebagai pilihan saat alumni mendaftar.</p>
        <form action="{{ route('admin.classrooms.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="add-name">Nama Kelas</label>
                <input type="text" id="add-name" name="name" class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    placeholder="Contoh: XII RPL 1" value="{{ old('name') }}" autofocus required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="add-description">Deskripsi <span style="font-weight:400; text-transform:none;">(opsional)</span></label>
                <textarea id="add-description" name="description" class="form-input {{ $errors->has('description') ? 'is-invalid' : '' }}"
                    placeholder="Contoh: Rekayasa Perangkat Lunak 1" rows="3"
                    style="resize: vertical; min-height: 80px;">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-add')">Batal</button>
                <button type="submit" class="btn-submit"><i class='bx bx-save'></i> Simpan Kelas</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Edit Kelas ────────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-edit" onclick="handleOverlayClick(event, 'modal-edit')">
    <div class="modal-box">
        <h3 class="modal-title"><i class='bx bx-edit-alt' style="color: #6366f1;"></i> Edit Kelas</h3>
        <p class="modal-subtitle">Perbarui nama atau deskripsi kategori kelas.</p>
        <form id="form-edit-classroom" action="" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="edit-name">Nama Kelas</label>
                <input type="text" id="edit-name" name="name" class="form-input" placeholder="Contoh: XII RPL 1" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit-description">Deskripsi <span style="font-weight:400; text-transform:none;">(opsional)</span></label>
                <textarea id="edit-description" name="description" class="form-input"
                    placeholder="Deskripsi singkat kelas" rows="3"
                    style="resize: vertical; min-height: 80px;"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-edit')">Batal</button>
                <button type="submit" class="btn-submit"><i class='bx bx-save'></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Modal Helpers ──────────────────────────────────────────
    function openAddModal() {
        document.getElementById('modal-add').classList.add('active');
        setTimeout(() => document.getElementById('add-name').focus(), 100);
    }

    function openEditModal(id, name, description) {
        const form = document.getElementById('form-edit-classroom');
        form.action = `/admin/classrooms/${id}`;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-description').value = description;
        document.getElementById('modal-edit').classList.add('active');
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
            closeModal('modal-add');
            closeModal('modal-edit');
        }
    });

    // Auto-open add modal if validation errors exist (on redirect back)
    @if($errors->any() && old('name') !== null)
        openAddModal();
    @endif

    // ── Delete Confirm ─────────────────────────────────────────
    function confirmDelete(id, name, memberCount) {
        const hasMembers = memberCount > 0;
        Swal.fire({
            title: `Hapus Kelas "${name}"?`,
            html: hasMembers
                ? `<p>Kelas ini memiliki <strong>${memberCount} anggota</strong>. Data kelas pada profil anggota akan dikosongkan, namun akun mereka tetap ada.</p>`
                : `<p>Kelas <strong>${name}</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: 'var(--border-color)',
            confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: { popup: 'swal-premium-popup' }
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById(`form-delete-classroom-${id}`).submit();
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
