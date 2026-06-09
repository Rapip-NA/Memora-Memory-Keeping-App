<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $q = $request->input('q');
        
        $users = collect();
        $posts = collect();
        
        if ($q) {
            $users = User::where('name', 'like', "%$q%")->orWhere('bio', 'like', "%$q%")->get();
            $posts = Post::where('content', 'like', "%$q%")
                ->with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])
                ->latest()
                ->paginate(20);
        } else {
            $posts = Post::with(['user', 'comments.user', 'likes', 'bookmarks', 'poll.options', 'poll.votes'])
                ->latest()
                ->paginate(20);
        }
        
        return view('desktop.explore', compact('currentUser', 'users', 'posts', 'q'));
    }

    public function users(Request $request)
    {
        $currentUser = auth()->user();
        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        // Determine which classroom to show
        $selectedClassroomId = $request->query('classroom_id');
        
        // If not specified, default to the user's classroom
        if (!$selectedClassroomId) {
            $selectedClassroomId = $currentUser->classroom_id;
        }

        // If still no classroom selected, default to first classroom
        if (!$selectedClassroomId && $classrooms->count() > 0) {
            $selectedClassroomId = $classrooms->first()->id;
        }
        
        if (!$selectedClassroomId) {
            $users = collect();
            $classroom = null;
        } else {
            $classroom = \App\Models\Classroom::find($selectedClassroomId);
            $query = User::where('classroom_id', $selectedClassroomId)
                ->where('status', 'active')
                ->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('nickname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate(15)->withQueryString();
        }

        return view('desktop.users', compact('currentUser', 'classroom', 'classrooms', 'users'));
    }
}
