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
        $selectedAlbum = $request->query('album');
        
        // Group photos by album to build folder list
        $albums = GalleryPhoto::selectRaw('IFNULL(NULLIF(album, ""), "Lainnya") as album_name')
            ->selectRaw('count(*) as photo_count')
            ->selectRaw('max(created_at) as latest_photo_at')
            ->groupBy('album_name')
            ->orderBy('latest_photo_at', 'desc')
            ->get()
            ->map(function ($item) {
                $rawAlbum = $item->album_name === 'Lainnya' ? ['', null, 'Lainnya'] : [$item->album_name];
                $latestPhoto = GalleryPhoto::whereIn('album', $rawAlbum)
                    ->latest()
                    ->first();
                $item->cover_url = $latestPhoto ? \Storage::url($latestPhoto->file_path) : null;
                return $item;
            });

        $allAlbums = $albums->pluck('album_name')->toArray();

        // If an album is selected, fetch the photos inside it
        $photos = collect();
        if ($selectedAlbum) {
            $query = GalleryPhoto::with('user')->latest();
            if ($selectedAlbum === 'Lainnya') {
                $query->where(function($q) {
                    $q->whereNull('album')->orWhere('album', '')->orWhere('album', 'Lainnya');
                });
            } else {
                $query->where('album', $selectedAlbum);
            }
            $photos = $query->get();
        }

        return view('desktop.gallery', compact('photos', 'selectedAlbum', 'albums', 'allAlbums'));
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

        $user = auth()->user();

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
        $user = auth()->user();
        
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
        $user = auth()->user();
        
        if ($photo->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        Storage::disk('public')->delete($photo->file_path);
        $photo->delete();
        
        return redirect()->route('desktop.gallery')->with('success', 'Photo deleted successfully.');
    }
}
