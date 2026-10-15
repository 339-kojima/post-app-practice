<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_detail_shows_only_its_replies_in_chronological_order(): void
    {
        $viewer = User::factory()->create();
        $post = $this->createPost();
        $firstReply = $this->createReply($post, content: '最初の返信');
        $secondReply = $this->createReply($post, content: '次の返信');
        $otherPost = $this->createPost();
        $this->createReply($otherPost, content: '別ポストへの返信');

        $firstReply->forceFill(['created_at' => now()->subMinute()])->save();
        $secondReply->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($viewer)->get(route('posts.show', $post));

        $response->assertOk()
            ->assertSee($post->title)
            ->assertSee('最初の返信')
            ->assertSee('次の返信')
            ->assertDontSee('別ポストへの返信')
            ->assertSeeInOrder(['最初の返信', '次の返信']);
    }

    public function test_post_detail_shows_empty_reply_message_when_there_are_no_replies(): void
    {
        $viewer = User::factory()->create();
        $post = $this->createPost();

        $this->actingAs($viewer)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('まだリプライはありません。');
    }

    public function test_authenticated_user_can_create_a_reply(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        $response = $this->actingAs($user)->post(route('replies.store', $post), [
            'content' => '読んだよ！',
        ]);

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseHas('replies', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => '読んだよ！',
        ]);
        $this->get(route('posts.show', $post))->assertSee('読んだよ！');
    }

    public function test_empty_and_whitespace_only_reply_bodies_are_rejected(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        foreach (['', " \t\n "] as $content) {
            $this->actingAs($user)
                ->from(route('posts.show', $post))
                ->post(route('replies.store', $post), ['content' => $content])
                ->assertRedirect(route('posts.show', $post))
                ->assertSessionHasErrors(['content' => '本文を入力してください']);
        }

        $this->assertDatabaseCount('replies', 0);
    }

    public function test_reply_author_can_delete_their_own_reply(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost();
        $reply = $this->createReply($post, $author);

        $this->actingAs($author)
            ->delete(route('replies.destroy', [$post, $reply]))
            ->assertRedirect(route('posts.show', $post));

        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }

    public function test_reply_cannot_be_deleted_by_another_user(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = $this->createPost();
        $reply = $this->createReply($post, $author);

        $this->actingAs($otherUser)
            ->get(route('posts.show', $post))
            ->assertDontSee(route('replies.destroy', [$post, $reply]));

        $this->actingAs($otherUser)
            ->delete(route('replies.destroy', [$post, $reply]))
            ->assertForbidden();

        $this->assertDatabaseHas('replies', ['id' => $reply->id]);
    }

    public function test_timeline_links_to_post_detail_and_keeps_existing_owner_actions(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $this->actingAs($author)
            ->get(route('posts.index'))
            ->assertOk()
            ->assertSee(route('posts.show', $post))
            ->assertSee(route('posts.edit', $post))
            ->assertSee(route('posts.destroy', $post));
    }

    private function createPost(?User $author = null): Post
    {
        $author ??= User::factory()->create();
        $category = Category::firstOrCreate(['name' => '雑記']);

        return Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'テストポスト',
            'content' => 'テスト本文',
        ]);
    }

    private function createReply(Post $post, ?User $author = null, string $content = '返信です'): Reply
    {
        $author ??= User::factory()->create();

        return Reply::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => $content,
        ]);
    }
}
