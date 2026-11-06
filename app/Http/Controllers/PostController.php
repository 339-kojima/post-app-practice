<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::orderBy('id')->get();
        $selectedTag = null;
        if ($request->filled('tag')) {
            $tagId = $request->query('tag');
            abort_unless(is_scalar($tagId) && ctype_digit((string) $tagId), 404);

            $selectedTag = Tag::findOrFail($tagId);
        }

        // 投稿者とトピックをあわせて取得し、新しい投稿順で一覧表示する。
        $posts = Post::with(['user', 'category'])
            ->with('tags')
            ->withCount('likes')
            ->withExists(['likes as liked_by_user' => fn ($query) => $query->where('users.id', auth()->id())])
            ->withExists(['bookmarks as bookmarked_by_user' => fn ($query) => $query->where('users.id', auth()->id())])
            ->when($selectedTag, fn ($query) => $query->whereHas(
                'tags',
                fn ($tagQuery) => $tagQuery->whereKey($selectedTag->id),
            ))
            ->latest()
            ->get();

        return view('posts.index', compact('posts', 'tags', 'selectedTag'));
    }

    public function create()
    {
        $categories = Category::orderBy('id')->get();
        $tags = Tag::orderBy('id')->get();

        return view('posts.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'title' => ['required', 'string', 'max:255'],
                'category_id' => ['required', 'exists:categories,id'],
                'content' => ['required', 'string', 'regex:/\S/'],
                'tags' => ['sometimes', 'array'],
                'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
            ],
            [
                'title.required' => 'タイトルを入力してください',
                'title.max' => 'タイトルは255文字以内で入力してください',
                'category_id.required' => 'トピックを選択してください',
                'category_id.exists' => '選択したトピックが正しくありません',
                'content.required' => '本文を入力してください',
                'content.regex' => '本文を入力してください',
                'tags.array' => 'タグの選択が正しくありません',
                'tags.*.integer' => '選択したタグが正しくありません',
                'tags.*.distinct' => 'タグを重複して選択することはできません',
                'tags.*.exists' => '選択したタグが正しくありません',
            ],
        );

        $tagIds = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post = $request->user()->posts()->create($validated);
        $post->tags()->sync($tagIds);

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
        $tags = Tag::orderBy('id')->get();
        $post->load('tags');

        return view('posts.edit', compact('post', 'categories', 'tags'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
        ], [
            'tags.array' => 'タグの選択が正しくありません',
            'tags.*.integer' => '選択したタグが正しくありません',
            'tags.*.distinct' => 'タグを重複して選択することはできません',
            'tags.*.exists' => '選択したタグが正しくありません',
        ]);

        $tagIds = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post->fill($validated);
        $post->updated_at = now()->greaterThan($post->created_at)
            ? now()
            : $post->created_at->copy()->addSecond();
        $post->save();
        $post->tags()->sync($tagIds);

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }
}
