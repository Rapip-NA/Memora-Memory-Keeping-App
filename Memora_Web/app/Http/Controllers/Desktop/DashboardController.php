<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\GalleryPhoto;
use App\Models\Notification;
use App\Models\Bookmark;

class DashboardController extends Controller
{
    public function index()
    {
        return view('desktop.dashboard');
    }

    public function data()
    {
        $totalUsers = User::count();
        $totalPhotos = GalleryPhoto::count();
        $totalPosts = Post::count();
        
        $events = Post::where('content', 'like', '%Acara:%')->orWhere('content', 'like', '%acara%')->count();
        $reunions = Post::where('content', 'like', '%reuni%')->count();

        $distribution = [
            max(0, $totalPosts - $events - $reunions),
            $reunions,
            $events,
            $totalPhotos
        ];

        $months = [];
        $activityPhotos = [];
        $activityPosts = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M');
            
            $photosCount = GalleryPhoto::whereYear('created_at', $date->year)
                                       ->whereMonth('created_at', $date->month)
                                       ->count();
                                       
            $postsCount = Post::whereYear('created_at', $date->year)
                              ->whereMonth('created_at', $date->month)
                              ->count();
                              
            $activityPhotos[] = $photosCount;
            $activityPosts[] = $postsCount;
        }

        return response()->json([
            'stats' => [
                'users' => $totalUsers,
                'photos' => $totalPhotos,
                'posts' => $totalPosts,
                'events' => $events,
                'reunions' => $reunions
            ],
            'distribution' => $distribution,
            'activity' => [
                'labels' => $months,
                'photos' => $activityPhotos,
                'posts' => $activityPosts
            ]
        ]);
    }
}
