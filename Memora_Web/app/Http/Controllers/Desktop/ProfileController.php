<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user() ?? User::first();
        $posts = Post::where('user_id', $currentUser->id)
                    ->with(['user', 'comments.user', 'likes', 'bookmarks'])
                    ->latest()
                    ->paginate(10);
        return view('desktop.profile', compact('posts', 'currentUser'));
    }

    public function update(Request $request)
    {
        $user = auth()->user() ?? User::first();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'city' => 'nullable|string',
            'born_date' => 'nullable|date',
            'photo' => 'nullable|image|max:5120',
            'banner_photo' => 'nullable|image|max:10240',
        ]);
        
        $data = $request->only(['name', 'bio', 'born_date', 'city']);
        
        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
                if (config('filesystems.default') === 's3') Storage::disk('s3')->delete($user->photo);
            }
            $file = $request->file('photo');
            $filename = Str::uuid() . '.jpg';
            $relativePath = 'photos/profiles/' . $filename;
            
            $destinationPath = storage_path('app/public/photos/profiles');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $manager = new ImageManager(new Driver());
            $img = $manager->decode($file->path());
            $img->cover(800, 800);
            $img->save($destinationPath . '/' . $filename, quality: 90);
            
            // Backup ke Cloud
            if (!empty(config('filesystems.disks.s3.key'))) {
                Storage::disk('s3')->put($relativePath, file_get_contents($destinationPath . '/' . $filename));
            }
            
            $data['photo'] = $relativePath;
        }
        
        if ($request->hasFile('banner_photo')) {
            if ($user->banner_photo) {
                Storage::disk('public')->delete($user->banner_photo);
                if (config('filesystems.default') === 's3') Storage::disk('s3')->delete($user->banner_photo);
            }
            $file = $request->file('banner_photo');
            $filename = Str::uuid() . '.jpg';
            $relativePath = 'photos/profiles/banners/' . $filename;
            
            $destinationPath = storage_path('app/public/photos/profiles/banners');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $manager = new ImageManager(new Driver());
            $img = $manager->decode($file->path());
            $img->cover(1500, 500);
            $img->save($destinationPath . '/' . $filename, quality: 90);

            // Backup ke Cloud
            if (!empty(config('filesystems.disks.s3.key'))) {
                Storage::disk('s3')->put($relativePath, file_get_contents($destinationPath . '/' . $filename));
            }
            
            $data['banner_photo'] = $relativePath;
        }
        
        $user->update($data);
        
        return redirect()->back()->with('success', 'Profile berhasil diperbarui.');
    }
}
