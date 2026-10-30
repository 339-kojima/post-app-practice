<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        // 投稿者とトピックをあわせて取得し、新しい投稿順で一覧表示する。
        $posts = Post::with(['user', 'category'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_user' => fn ($query) => $query->where('users.id', auth()->id())])
            ->latest()
            ->get();

        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::orderBy('id')->get();

        return view('posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'title' => ['required', 'string', 'max:255'],
                'category_id' => ['required', 'exists:categories,id'],
                'content' => ['required', 'string', 'regex:/\S/'],
            ],
            [
                'title.required' => 'タイトルを入力してください',
                'title.max' => 'タイトルは255文字以内で入力してください',
                'category_id.required' => 'トピックを選択してください',
                'category_id.exists' => '選択したトピックが正しくありません',
                'content.required' => '本文を入力してください',
                'content.regex' => '本文を入力してください',
            ],
        );

        $request->user()->posts()->create($validated);

        return redirect()->route('posts.index');
    }

    public function show(Post $post)
    {
        $post->load(['user', 'category', 'replies.user']);
        $post->setRelation('replies', $post->replies->sortBy('created_at')->values());

        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $categories = Category::orderBy('id')->get();

        return view('posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $post->fill($validated);
        $post->updated_at = now()->greaterThan($post->created_at)
            ? now()
            : $post->created_at->copy()->addSecond();
        $post->save();

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }
}
