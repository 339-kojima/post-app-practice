<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Reply;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate(
            ['content' => ['required', 'string']],
            ['content.required' => '本文を入力してください'],
        );

        $post->replies()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function destroy(Post $post, Reply $reply)
    {
        abort_unless($reply->post_id === $post->id, 404);

        $this->authorize('delete', $reply);
        $reply->delete();

        return redirect()->route('posts.show', $post);
    }
}
