<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ブックマーク / つぶやき投稿アプリ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Sans", "Noto Sans JP", sans-serif; background: #fff; color: #0F1419; }
        a { color: inherit; text-decoration: none; }
        .col { max-width: 600px; margin: 0 auto; min-height: 100dvh; border-left: 1px solid #EFF1F4; border-right: 1px solid #EFF1F4; }
        header { padding: 1rem; border-bottom: 1px solid #EFF1F4; }
        header a { color: #2F5AA8; font-size: .85rem; }
        h1 { margin-top: .65rem; font-size: 1.1rem; }
        article { padding: 1rem; border-bottom: 1px solid #EFF1F4; }
        .meta { color: #5B6570; font-size: .82rem; }
        h2 { margin: .35rem 0; font-size: 1rem; }
        .content { line-height: 1.7; overflow-wrap: anywhere; }
        .actions { margin-top: .6rem; display: flex; gap: 1rem; align-items: center; color: #5B6570; font-size: .82rem; }
        button { padding: 0; border: 0; background: transparent; color: #2F5AA8; font: inherit; cursor: pointer; }
        .empty { padding: 3rem 1rem; text-align: center; color: #5B6570; }
    </style>
</head>
<body>
    <div class="col">
        <header>
            <a href="{{ route('posts.index') }}">← ホーム</a>
            <h1>ブックマーク</h1>
        </header>
        <main>
            @forelse ($posts as $post)
                <article>
                    <p class="meta">{{ $post->user->name }} · {{ $post->created_at->format('n月j日 H:i') }} · {{ $post->category->name }}</p>
                    <h2><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h2>
                    <p class="content">{{ $post->content }}</p>
                    @if ($post->tags->isNotEmpty())
                        <p class="meta">@foreach ($post->tags as $tag)<span>#{{ $tag->name }} </span>@endforeach</p>
                    @endif
                    <div class="actions">
                        <span>{{ $post->likes_count }} いいね</span>
                        <form action="{{ route('posts.bookmarks.toggle', $post) }}" method="POST">
                            @csrf
                            <button type="submit" aria-pressed="true">ブックマーク解除</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="empty">ブックマークしたポストはありません。</p>
            @endforelse
        </main>
    </div>
</body>
</html>
