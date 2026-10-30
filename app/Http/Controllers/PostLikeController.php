<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostLikeController extends Controller
{
    public function store(Post $post)
    {
        $post->likes()->syncWithoutDetaching([auth()->id()]);

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $post->likes()->detach(auth()->id());

        return redirect()->route('posts.index');
    }
}
