<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_bookmark_any_post_and_see_it_in_their_list(): void
    {
        $user = User::factory()->create();
        $ownPost = $this->createPost($user, '自分の投稿');
        $otherPost = $this->createPost(User::factory()->create(), '他人の投稿');

        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertSee('ブックマーク', false);

        $this->actingAs($user)
            ->post(route('posts.bookmarks.toggle', $ownPost))
            ->assertRedirect();
        $this->post(route('posts.bookmarks.toggle', $otherPost))
            ->assertRedirect();

        $this->assertDatabaseCount('bookmarks', 2);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'post_id' => $ownPost->id]);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'post_id' => $otherPost->id]);

        $this->get(route('bookmarks.index'))
            ->assertOk()
            ->assertSee('自分の投稿')
            ->assertSee('他人の投稿');
    }

    public function test_bookmark_toggle_removes_existing_bookmark(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost(User::factory()->create());
        $user->bookmarkedPosts()->attach($post->id);

        $this->actingAs($user)
            ->post(route('posts.bookmarks.toggle', $post))
            ->assertRedirect();

        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'post_id' => $post->id]);
        $this->get(route('bookmarks.index'))->assertDontSee($post->title);
    }

    public function test_bookmark_list_and_state_are_private_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $post = $this->createPost(User::factory()->create(), '保存済みだけのタイトル');
        $owner->bookmarkedPosts()->attach($post->id);

        $this->actingAs($viewer)
            ->get(route('bookmarks.index'))
            ->assertOk()
            ->assertDontSee('保存済みだけのタイトル');

        $this->get(route('posts.index'))
            ->assertSee('ブックマーク', false)
            ->assertDontSee('ブックマーク解除', false);

        $this->post(route('posts.bookmarks.toggle', $post))
            ->assertRedirect();

        $this->assertDatabaseHas('bookmarks', ['user_id' => $owner->id, 'post_id' => $post->id]);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $viewer->id, 'post_id' => $post->id]);
    }

    public function test_bookmark_routes_require_authentication(): void
    {
        $post = $this->createPost(User::factory()->create());

        $this->get(route('bookmarks.index'))->assertRedirect(route('login'));
        $this->post(route('posts.bookmarks.toggle', $post))->assertRedirect(route('login'));
    }

    private function createPost(User $author, string $title = 'テストポスト'): Post
    {
        $category = Category::firstOrCreate(['name' => '雑記']);

        return Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => $title,
            'content' => 'テスト本文',
        ]);
    }
}
