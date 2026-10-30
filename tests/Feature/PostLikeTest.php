<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_like_a_post_from_the_timeline(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();
        $postUpdatedAt = $post->updated_at->toDateTimeString();

        $this->actingAs($user)
            ->post(route('posts.likes.store', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('post_likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertSee('いいね済み · 1')
            ->assertSee('class="like-button is-liked"', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee('編集済み');
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'updated_at' => $postUpdatedAt,
        ]);
    }

    public function test_authenticated_user_can_remove_their_like(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();
        $post->likes()->attach($user->id);

        $this->actingAs($user)
            ->delete(route('posts.likes.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('post_likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertSee('いいね · 0')
            ->assertSee('class="like-button"', false)
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_a_user_can_like_a_post_only_once_and_count_is_shared(): void
    {
        $liker = User::factory()->create();
        $viewer = User::factory()->create();
        $post = $this->createPost();

        $this->actingAs($liker)->post(route('posts.likes.store', $post));
        $this->actingAs($liker)->post(route('posts.likes.store', $post));

        $this->assertDatabaseCount('post_likes', 1);
        $this->actingAs($viewer)
            ->get(route('posts.index'))
            ->assertSee('いいね · 1')
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('aria-pressed="true"', false);
    }

    public function test_like_routes_require_authentication(): void
    {
        $post = $this->createPost();

        $this->post(route('posts.likes.store', $post))
            ->assertRedirect(route('login'));
        $this->delete(route('posts.likes.destroy', $post))
            ->assertRedirect(route('login'));
    }

    private function createPost(): Post
    {
        $author = User::factory()->create();
        $category = Category::firstOrCreate(['name' => '雑記']);

        return Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'テストポスト',
            'content' => 'テスト本文',
        ]);
    }
}
