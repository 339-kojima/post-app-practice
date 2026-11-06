<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $posts = $request->user()->bookmarkedPosts()
            ->with(['user', 'category', 'tags'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_user' => fn ($query) => $query->where('users.id', $request->user()->id)])
            ->withExists(['bookmarks as bookmarked_by_user' => fn ($query) => $query->where('users.id', $request->user()->id)])
            ->latest('posts.created_at')
            ->get();

        return view('bookmarks.index', compact('posts'));
    }

    public function toggle(Request $request, Post $post)
    {
        $bookmarks = $request->user()->bookmarkedPosts();

        if ($bookmarks->whereKey($post->id)->exists()) {
            $bookmarks->detach($post->id);
        } else {
            $bookmarks->syncWithoutDetaching([$post->id]);
        }

        return redirect()->back();
    }
}
