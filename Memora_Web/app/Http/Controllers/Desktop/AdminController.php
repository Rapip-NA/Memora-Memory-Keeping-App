<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;

class AdminController extends Controller
{
    /**
     * Tampilkan antrean registrasi pengguna yang berstatus 'pending'.
     */
    public function validation(Request $request)
    {
        $users = User::where('status', 'pending')
                     ->oldest()
                     ->paginate(15);
                     
        return view('desktop.admin.validation', compact('users'));
    }

    /**
     * Setujui pendaftaran pengguna baru (ubah status ke 'active').
     */
    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengguna tidak sedang dalam status pending.');
        }
        
        $user->update(['status' => 'active']);
        
        // Buat notifikasi persetujuan
        Notification::create([
            'user_id' => $user->id,
            'type'    => 'approval',
            'data'    => ['message' => 'Akun kamu telah disetujui! Selamat bergabung di Memora.'],
        ]);
        
        return redirect()->back()->with('success', "Pendaftaran {$user->name} berhasil disetujui.");
    }

    /**
     * Tolak pendaftaran pengguna baru (ubah status ke 'inactive').
     */
    public function reject(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengguna tidak sedang dalam status pending.');
        }
        
        $user->update(['status' => 'inactive']);
        
        return redirect()->back()->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    /**
     * Panel monitoring postingan terbaru untuk kebutuhan moderasi.
     */
    public function monitoring(Request $request)
    {
        $query = Post::with(['user', 'comments.user', 'likes', 'bookmarks'])->latest();
        
        // Fitur pencarian postingan berdasarkan konten atau nama user
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $posts = $query->paginate(15);
        
        return view('desktop.admin.monitoring', compact('posts'));
    }

    /**
     * Hapus postingan bermasalah secara paksa oleh Admin.
     */
    public function deletePost(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $postContent = substr(strip_tags($post->content), 0, 30) . '...';
        
        $post->delete();
        
        return redirect()->back()->with('success', "Postingan (\"{$postContent}\") berhasil dihapus.");
    }

    // ─── Classroom Management ──────────────────────────────────────────────────

    /**
     * Tampilkan halaman manajemen kelas.
     */
    public function classrooms(Request $request)
    {
        $classrooms = \App\Models\Classroom::withCount('users')->orderBy('name')->get();
        return view('desktop.admin.classrooms', compact('classrooms'));
    }

    /**
     * Tambahkan kategori kelas baru.
     */
    public function storeClassroom(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:classrooms,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique'   => 'Nama kelas sudah terdaftar.',
            'name.max'      => 'Nama kelas maksimal 100 karakter.',
        ]);

        \App\Models\Classroom::create($request->only('name', 'description'));

        return redirect()->route('admin.classrooms')->with('success', "Kelas \"{$request->name}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui data kategori kelas.
     */
    public function updateClassroom(Request $request, $id)
    {
        $classroom = \App\Models\Classroom::findOrFail($id);

        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:classrooms,name,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique'   => 'Nama kelas sudah terdaftar.',
            'name.max'      => 'Nama kelas maksimal 100 karakter.',
        ]);

        $classroom->update($request->only('name', 'description'));

        return redirect()->route('admin.classrooms')->with('success', "Kelas \"{$classroom->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus kategori kelas.
     */
    public function destroyClassroom($id)
    {
        $classroom = \App\Models\Classroom::findOrFail($id);
        $name = $classroom->name;
        $classroom->delete();

        return redirect()->route('admin.classrooms')->with('success', "Kelas \"{$name}\" berhasil dihapus.");
    }

    /**
     * Tampilkan halaman daftar anggota (user) beserta filter.
     */
    public function users(Request $request)
    {
        $query = User::with('classroom')->latest();

        // Filter berdasarkan pencarian nama, email, nickname
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan kelas
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        // Filter berdasarkan kategori/role user
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(15)->withQueryString();
        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        return view('desktop.admin.users', compact('users', 'classrooms'));
    }

    /**
     * Tambahkan anggota baru secara manual oleh Admin.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'role'         => ['required', 'in:member,admin'],
            'status'       => ['required', 'in:pending,active,inactive'],
        ], [
            'name.required'         => 'Nama wajib diisi.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar.',
            'password.required'     => 'Kata sandi wajib diisi.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'classroom_id.required' => 'Kelas wajib dipilih.',
            'classroom_id.exists'   => 'Kelas tidak valid.',
            'role.required'         => 'Kategori user wajib diisi.',
            'role.in'               => 'Kategori user tidak valid.',
            'status.required'       => 'Status wajib diisi.',
            'status.in'             => 'Status tidak valid.',
        ]);

        User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'password'     => \Illuminate\Support\Facades\Hash::make($request->password),
            'classroom_id' => $request->classroom_id,
            'role'         => $request->role,
            'status'       => $request->status,
        ]);

        return redirect()->route('admin.users')->with('success', "Anggota \"{$request->name}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui data anggota oleh Admin.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password'     => ['nullable', 'string', 'min:8'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'role'         => ['required', 'in:member,admin'],
            'status'       => ['required', 'in:pending,active,inactive'],
        ], [
            'name.required'         => 'Nama wajib diisi.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'classroom_id.required' => 'Kelas wajib dipilih.',
            'classroom_id.exists'   => 'Kelas tidak valid.',
            'role.required'         => 'Kategori user wajib diisi.',
            'role.in'               => 'Kategori user tidak valid.',
            'status.required'       => 'Status wajib diisi.',
            'status.in'             => 'Status tidak valid.',
        ]);

        $data = [
            'name'         => $request->name,
            'email'        => $request->email,
            'classroom_id' => $request->classroom_id,
            'role'         => $request->role,
            'status'       => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', "Data anggota \"{$user->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus anggota secara permanen.
     */
    public function destroyUser($id)
    {
        if (auth()->id() == $id) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users')->with('success', "Anggota \"{$name}\" berhasil dihapus.");
    }
}
