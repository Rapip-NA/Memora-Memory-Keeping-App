<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $album = $request->query('album');
        $query = GalleryPhoto::with('user')->latest();
        
        if ($album && $album !== 'Semua') {
            $query->where('album', $album);
        }
        
        $photos = $query->get();
        
        $albums = GalleryPhoto::whereNotNull('album')
                    ->where('album', '!=', '')
                    ->distinct()
                    ->pluck('album')
                    ->toArray();
                    
        // Include default albums if they don't exist in DB to keep the UI consistent initially
        $defaultAlbums = ['Wisuda 2024', 'Malam Keakraban', 'Album Kelas', 'Lainnya'];
        $allAlbums = array_unique(array_merge($defaultAlbums, $albums));
        
        return view('desktop.gallery', compact('photos', 'album', 'allAlbums'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'photo'            => 'required|image|mimes:jpg,jpeg,png,gif|max:5120',
            'caption'          => 'nullable|string|max:300',
            'album_select'     => 'nullable|string|max:100',
            'new_album_name'   => 'nullable|string|max:100',
        ]);

        $file      = $request->file('photo');
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath  = $file->storeAs('photos/gallery', $filename, 'public');

        $user = auth()->user() ?? User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'demo' . time() . '@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $albumName = $request->album_select;
        if ($albumName === 'new_album' && !empty($request->new_album_name)) {
            $albumName = $request->new_album_name;
        } elseif ($albumName === 'new_album') {
            $albumName = 'Lainnya'; // Fallback if they selected new but typed nothing
        }

        GalleryPhoto::create([
            'user_id'         => $user->id,
            'file_path'       => $filePath,
            'caption'         => $request->caption,
            'album'           => $albumName,
            'tagged_user_ids' => [],
        ]);

        return redirect()->route('desktop.gallery')->with('success', 'Foto berhasil diunggah.');
    }

    public function update(Request $request, $id)
    {
        $photo = GalleryPhoto::findOrFail($id);
        $user = auth()->user() ?? User::first();
        
        if ($photo->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'caption'          => 'nullable|string|max:300',
            'album_select'     => 'nullable|string|max:100',
            'new_album_name'   => 'nullable|string|max:100',
        ]);
        
        $albumName = $request->album_select;
        if ($albumName === 'new_album' && !empty($request->new_album_name)) {
            $albumName = $request->new_album_name;
        } elseif ($albumName === 'new_album') {
            $albumName = 'Lainnya';
        }
        
        $photo->update([
            'caption' => $request->caption,
            'album'   => $albumName,
        ]);
        
        return redirect()->route('desktop.gallery')->with('success', 'Caption updated successfully.');
    }

    public function destroy($id)
    {
        $photo = GalleryPhoto::findOrFail($id);
        $user = auth()->user() ?? User::first();
        
        if ($photo->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        Storage::disk('public')->delete($photo->file_path);
        $photo->delete();
        
        return redirect()->route('desktop.gallery')->with('success', 'Photo deleted successfully.');
    }
}
