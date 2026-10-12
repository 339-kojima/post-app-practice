<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }} / つぶやき投稿アプリ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Sans", "Noto Sans JP", sans-serif; background: #FFFFFF; color: #0F1419; font-feature-settings: "palt"; }
        a { text-decoration: none; color: inherit; }
        .col { max-width: 600px; margin: 0 auto; min-height: 100dvh; border-left: 1px solid #EFF1F4; border-right: 1px solid #EFF1F4; }
        .chrome { position: sticky; top: 0; z-index: 10; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid #EFF1F4; }
        .chrome-row { display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; padding: 0.6rem 1rem; }
        .brand { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 1.05rem; letter-spacing: -0.02em; }
        .brand .mark { width: 28px; height: 28px; border-radius: 38%; background: linear-gradient(135deg, #FFB03A, #FF7A59); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; flex-shrink: 0; }
        .me { display: flex; align-items: center; gap: 0.7rem; min-width: 0; }
        .me .name { font-size: 0.8rem; color: #5B6570; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .logout-btn { background: #FFFFFF; color: #0F1419; border: 1px solid #D3D9DE; padding: 0.38rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; cursor: pointer; font-family: inherit; flex-shrink: 0; }
        .page-title { padding: 0.55rem 1rem 0.7rem; font-weight: 800; font-size: 1.06rem; }
        .back { color: #5B6570; font-size: 0.85rem; }
        .post, .reply { display: flex; gap: 0.75rem; padding: 1rem; border-bottom: 1px solid #EFF1F4; }
        .avatar { width: 44px; height: 44px; border-radius: 999px; flex-shrink: 0; background: linear-gradient(135deg, #5B8DEF, #3F5FD0); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; }
        .body { flex: 1; min-width: 0; }
        .head { display: flex; align-items: baseline; gap: 0.35rem; flex-wrap: wrap; }
        .name { font-weight: 700; font-size: 0.94rem; }
        .time { color: #5B6570; font-size: 0.82rem; }
        .category { margin-left: auto; border-radius: 999px; padding: 0.1rem 0.6rem; background: #F0F1F3; color: #57606A; font-size: 0.7rem; font-weight: 700; }
        .title { font-size: 1.1rem; font-weight: 800; margin: 0.65rem 0 0.35rem; }
        .content { font-size: 0.96rem; line-height: 1.8; overflow-wrap: anywhere; white-space: pre-wrap; }
        .section-title { padding: 0.9rem 1rem; font-weight: 800; border-bottom: 1px solid #EFF1F4; }
        .reply .avatar { width: 38px; height: 38px; font-size: 1rem; }
        .reply-content { margin-top: 0.25rem; line-height: 1.7; overflow-wrap: anywhere; white-space: pre-wrap; }
        .delete { margin-top: 0.4rem; background: none; border: 0; color: #5B6570; font: inherit; font-size: 0.8rem; cursor: pointer; }
        .delete:hover { color: #D6402C; text-decoration: underline; }
        .empty { color: #5B6570; text-align: center; padding: 2rem 1rem; }
        .composer { padding: 1rem; border-bottom: 1px solid #EFF1F4; }
        textarea { display: block; width: 100%; min-height: 88px; padding: 0.7rem; border: 1px solid #D3D9DE; border-radius: 8px; font: inherit; resize: vertical; }
        textarea:focus { outline: 2px solid #E8792B; outline-offset: 1px; }
        .error { color: #B42318; font-size: 0.85rem; margin: 0.35rem 0; }
        .submit { margin-top: 0.6rem; background: #E8792B; color: #fff; border: 0; padding: 0.55rem 1.1rem; border-radius: 999px; font: inherit; font-weight: 700; cursor: pointer; }
        .submit:hover { background: #CB6120; }
    </style>
</head>
<body>
    <div class="col">
        <header class="chrome">
            <div class="chrome-row">
                <a href="{{ route('posts.index') }}" class="brand"><span class="mark">つ</span>つぶやき投稿アプリ</a>
                <div class="me">
                    <span class="name">{{ auth()->user()->name }}（{{ auth()->user()->email }}）</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-btn">ログアウト</button>
                    </form>
                </div>
            </div>
            <div class="page-title"><a href="{{ route('posts.index') }}" class="back">← ホーム</a>　ポスト</div>
        </header>

        <main>
            <article class="post">
                <div class="avatar">{{ mb_substr($post->user->name, 0, 1) }}</div>
                <div class="body">
                    <div class="head">
                        <span class="name">{{ $post->user->name }}</span>
                        <span class="time">・{{ $post->created_at->format('n月j日 H:i') }}</span>
                        <span class="category">{{ $post->category->name }}</span>
                    </div>
                    <h1 class="title">{{ $post->title }}</h1>
                    <p class="content">{{ $post->content }}</p>
                </div>
            </article>

            <h2 class="section-title">リプライ</h2>
            @forelse ($post->replies as $reply)
                <article class="reply">
                    <div class="avatar">{{ mb_substr($reply->user->name, 0, 1) }}</div>
                    <div class="body">
                        <div class="head">
                            <span class="name">{{ $reply->user->name }}</span>
                            <span class="time">・{{ $reply->created_at->format('n月j日 H:i') }}</span>
                        </div>
                        <p class="reply-content">{{ $reply->content }}</p>
                        @can('delete', $reply)
                            <form action="{{ route('replies.destroy', [$post, $reply]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete">削除</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @empty
                <p class="empty">まだリプライはありません。</p>
            @endforelse

            <form class="composer" action="{{ route('replies.store', $post) }}" method="POST">
                @csrf
                <label for="content" class="section-title" style="display: block; padding: 0 0 0.6rem; border: 0;">リプライを書く</label>
                <textarea id="content" name="content" aria-describedby="content-error" placeholder="返信を入力">{{ old('content') }}</textarea>
                @error('content')
                    <p class="error" id="content-error">{{ $message }}</p>
                @enderror
                <button type="submit" class="submit">リプライ</button>
            </form>
        </main>
    </div>
</body>
</html>
