# LifeAfter — Laravel REST API

> API backend untuk aplikasi alumni angkatan berbasis Flutter. Dibangun dengan **Laravel 13** dan **Laravel Sanctum** untuk token-based authentication.

---

## 📋 Persyaratan

| Kebutuhan | Versi Minimum |
|-----------|--------------|
| PHP | >= 8.3 |
| Composer | >= 2.x |
| MySQL | >= 8.0 |
| Node.js | >= 18.x _(opsional, untuk asset)_ |

---

## ⚙️ Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/<username>/lifeafter-api.git
cd lifeafter-api
```

### 2. Install Dependensi PHP

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan koneksi database:

```env
APP_NAME=LifeAfterAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lifeafter
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Buat Database

Buat database baru di MySQL:

```sql
CREATE DATABASE lifeafter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Jalankan Migration & Seeder

```bash
php artisan migrate
php artisan db:seed
```

### 6. Buat Symlink Storage

```bash
php artisan storage:link
```

> Ini diperlukan agar foto profil yang diupload bisa diakses via URL.

### 7. Jalankan Server

```bash
php artisan serve
```

API siap diakses di: `http://localhost:8000`

---

## 📁 Struktur Folder Penting

```
app/
├── Helpers/
│   └── ApiResponse.php          # Helper class untuk format response JSON
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php   # Register, login, logout, me
│   │   ├── UserController.php   # CRUD profil & upload foto
│   │   └── Admin/
│   │       └── StatsController.php  # Statistik platform (admin only)
│   ├── Middleware/
│   │   ├── CheckUserStatus.php  # Cek status akun aktif
│   │   └── CheckAdminRole.php   # Cek role admin
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── RegisterRequest.php
│   │   │   └── LoginRequest.php
│   │   └── User/
│   │       └── UpdateProfileRequest.php
│   └── Resources/
│       ├── UserResource.php         # Data user lengkap
│       └── UserMinimalResource.php  # Data user ringkas (list & map)
├── Models/
│   ├── User.php
│   ├── Post.php
│   ├── Comment.php
│   ├── GalleryPhoto.php
│   ├── Event.php
│   ├── EventRsvp.php
│   ├── Notification.php
│   └── Like.php
bootstrap/
└── app.php                      # Konfigurasi middleware & exception handler
routes/
└── api.php                      # Semua API routes
postman/
└── LifeAfter_API.json           # Postman Collection siap import
```

---

## 🔌 Endpoint yang Tersedia

### 🔐 Authentication

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| `POST` | `/api/auth/register` | Daftar akun baru (status: pending) | ❌ |
| `POST` | `/api/auth/login` | Login & dapatkan token | ❌ |
| `POST` | `/api/auth/logout` | Logout & hapus token aktif | ✅ |
| `GET` | `/api/auth/me` | Data user yang sedang login | ✅ |

### 👤 Users

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| `GET` | `/api/users` | Daftar anggota aktif (filter + pagination) | ✅ |
| `GET` | `/api/users/map` | Anggota aktif dengan koordinat (untuk peta) | ✅ |
| `GET` | `/api/users/{id}` | Detail profil satu anggota | ✅ |
| `PUT` | `/api/users/{id}` | Update profil sendiri | ✅ |
| `POST` | `/api/users/{id}/photo` | Upload foto profil sendiri | ✅ |

**Query params untuk `GET /api/users`:**

| Param | Contoh | Keterangan |
|-------|--------|------------|
| `city` | `?city=Jakarta` | Filter by kota (partial match) |
| `job` | `?job=engineer` | Filter by pekerjaan (partial match) |
| `search` | `?search=rafif` | Cari by nama atau nickname |
| `page` | `?page=2` | Nomor halaman (20 per halaman) |

### 🛡️ Admin

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| `GET` | `/api/admin/stats` | Statistik platform (admin only) | ✅ Admin |

---

## 📦 Format Response

### ✅ Response Sukses

```json
{
    "status": "success",
    "message": "Berhasil",
    "data": {
        "user": { }
    }
}
```

### ❌ Response Error

```json
{
    "status": "error",
    "message": "Pesan error yang deskriptif"
}
```

### ❌ Response Validasi Gagal (422)

```json
{
    "status": "error",
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email sudah terdaftar."],
        "password": ["Password minimal 8 karakter."]
    }
}
```

### 📄 Response Pagination (untuk `/api/users`)

```json
{
    "status": "success",
    "data": [ ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 98
    }
}
```

---

## 🔑 HTTP Status Code

| Kode | Arti |
|------|------|
| `200` | OK — Request berhasil |
| `201` | Created — Data berhasil dibuat |
| `401` | Unauthorized — Token tidak ada atau tidak valid |
| `403` | Forbidden — Tidak punya akses (akun inactive / bukan admin) |
| `404` | Not Found — Data tidak ditemukan |
| `422` | Unprocessable Entity — Validasi gagal |
| `429` | Too Many Requests — Rate limit terlampaui |

---

## 🧪 Akun Testing (dari Seeder)

| Role | Email | Password | Status |
|------|-------|----------|--------|
| Admin | `admin@lifeafter.test` | `password` | active |
| Member | `member@lifeafter.test` | `password` | active |

---

## 📬 Postman Collection

File collection sudah tersedia di `postman/LifeAfter_API.json`.

**Cara import:**
1. Buka Postman → klik **Import**
2. Pilih file `postman/LifeAfter_API.json`
3. Collection variables `base_url` dan `token` sudah dikonfigurasi
4. Jalankan **Login** terlebih dahulu — token tersimpan otomatis

---

## 📱 Catatan untuk Tim Flutter

### Cara Mengirim Token di Header

Setiap request ke endpoint yang membutuhkan autentikasi, tambahkan header berikut:

```
Authorization: Bearer <token_dari_login>
Accept: application/json
```

Contoh di Flutter dengan `http` package:

```dart
final response = await http.get(
  Uri.parse('$baseUrl/api/auth/me'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  },
);
```

### Cara Handle Error 401 & 403

```dart
final data = jsonDecode(response.body);

switch (response.statusCode) {
  case 401:
    // Token expired atau tidak valid — arahkan ke halaman login
    Navigator.pushReplacementNamed(context, '/login');
    break;
  case 403:
    // Akun belum aktif atau tidak punya akses
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(data['message'])),
    );
    break;
  case 422:
    // Validasi gagal — tampilkan errors per field
    final errors = data['errors'] as Map<String, dynamic>;
    errors.forEach((field, messages) {
      print('$field: ${(messages as List).first}');
    });
    break;
}
```

### Cara Handle Pagination

Response pagination tersedia di key `meta`:

```dart
final meta = data['meta'];
final currentPage = meta['current_page'] as int;
final lastPage    = meta['last_page'] as int;
final total       = meta['total'] as int;

// Cek apakah masih ada halaman berikutnya
final hasNextPage = currentPage < lastPage;

// Ambil halaman berikutnya
final nextUrl = '$baseUrl/api/users?page=${currentPage + 1}';
```

### Cara Upload Foto Profil

Gunakan `MultipartRequest` untuk upload file:

```dart
final request = http.MultipartRequest(
  'POST',
  Uri.parse('$baseUrl/api/users/$userId/photo'),
);

request.headers['Authorization'] = 'Bearer $token';
request.headers['Accept'] = 'application/json';

request.files.add(await http.MultipartFile.fromPath(
  'photo',
  filePath,
  contentType: MediaType('image', 'jpeg'),
));

final streamedResponse = await request.send();
final response = await http.Response.fromStream(streamedResponse);
final data = jsonDecode(response.body);
// data['data']['photo_url'] => URL foto yang baru diupload
```

---

## 🔄 Alur Registrasi & Approval

```
User daftar (POST /register)
    → Status: pending
    → Belum bisa login

Admin approve (ubah status='active' via admin panel / DB)
    → Status: active

User login (POST /login)
    → Dapat Bearer token
    → Akses semua endpoint protected
```

---

> **Dikerjakan oleh:** Rafif — Backend Team LifeAfter
> **Framework:** Laravel 13 | **Auth:** Laravel Sanctum | **DB:** MySQL 8.0
