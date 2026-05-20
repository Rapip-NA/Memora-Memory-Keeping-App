<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Kumpulkan statistik keseluruhan platform LifeAfter.
     * GET /api/admin/stats
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Total anggota aktif
        $totalMembers = User::where('role', 'member')
            ->where('status', 'active')
            ->count();

        // 2. Distribusi kota (top 10)
        $cityDistribution = User::where('role', 'member')
            ->where('status', 'active')
            ->whereNotNull('city')
            ->selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['city' => $row->city, 'count' => $row->count]);

        // 3. Distribusi pekerjaan (top 10)
        $jobDistribution = User::where('role', 'member')
            ->where('status', 'active')
            ->whereNotNull('job')
            ->selectRaw('job, COUNT(*) as count')
            ->groupBy('job')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['job' => $row->job, 'count' => $row->count]);

        // 4. Kelengkapan profil
        $completeMembers = User::where('role', 'member')
            ->where('status', 'active')
            ->whereNotNull('name')
            ->whereNotNull('city')
            ->whereNotNull('job')
            ->whereNotNull('born_date')
            ->whereNotNull('photo')
            ->count();

        $incompleteMembers = $totalMembers - $completeMembers;
        $completionPercentage = $totalMembers > 0
            ? round(($completeMembers / $totalMembers) * 100, 1)
            : 0.0;

        // 5–8. Konten & anggota pending
        $totalPosts   = Post::count();
        $totalGallery = GalleryPhoto::count();
        $totalEvents  = Event::count();
        $pendingMembers = User::where('status', 'pending')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_members'      => $totalMembers,
                'pending_members'    => $pendingMembers,
                'city_distribution'  => $cityDistribution,
                'job_distribution'   => $jobDistribution,
                'profile_completion' => [
                    'complete'   => $completeMembers,
                    'incomplete' => $incompleteMembers,
                    'percentage' => $completionPercentage,
                ],
                'total_posts'        => $totalPosts,
                'total_gallery'      => $totalGallery,
                'total_events'       => $totalEvents,
            ],
        ]);
    }
}
