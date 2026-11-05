<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_creation_form_lists_available_tags(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => 'Laravel']);

        $this->actingAs($user)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('ハッシュタグ（任意・複数選択可）')
            ->assertSee('#Laravel');
    }

    public function test_post_can_be_created_with_multiple_tags_or_without_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $laravel = Tag::create(['name' => 'Laravel']);
        $study = Tag::create(['name' => '学習記録']);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'タグ付きポスト',
                'category_id' => $category->id,
                'content' => 'タグを複数選択した本文',
                'tags' => [$laravel->id, $study->id],
            ])
            ->assertRedirect(route('posts.index'));

        $taggedPost = Post::where('title', 'タグ付きポスト')->firstOrFail();
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $taggedPost->id,
            'tag_id' => $laravel->id,
        ]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $taggedPost->id,
            'tag_id' => $study->id,
        ]);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'タグなしポスト',
                'category_id' => $category->id,
                'content' => 'タグを選ばない本文',
            ])
            ->assertRedirect(route('posts.index'));

        $untaggedPost = Post::where('title', 'タグなしポスト')->firstOrFail();
        $this->assertDatabaseCount('post_tag', 2);
        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertSee('class="post-tags"', false);

        $this->assertSame([], $untaggedPost->tags()->pluck('tags.id')->all());
    }

    public function test_author_can_replace_tags_when_editing_their_post(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => '技術メモ']);
        $oldTag = Tag::create(['name' => 'Laravel']);
        $newTag = Tag::create(['name' => 'PHP']);
        $post = $this->createPost($author, $category, '編集対象');
        $post->tags()->attach($oldTag);

        $this->actingAs($author)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('#Laravel');

        $this->put(route('posts.update', $post), [
            'title' => $post->title,
            'content' => $post->content,
            'category_id' => $category->id,
            'tags' => [$newTag->id],
        ])->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $oldTag->id,
        ]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $newTag->id,
        ]);
    }

    public function test_another_user_cannot_open_or_update_tags_on_a_post(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::create(['name' => '技術メモ']);
        $existingTag = Tag::create(['name' => 'Laravel']);
        $replacementTag = Tag::create(['name' => 'PHP']);
        $post = $this->createPost($author, $category, '他人のポスト');
        $post->tags()->attach($existingTag);

        $this->actingAs($otherUser)
            ->get(route('posts.edit', $post))
            ->assertForbidden();

        $this->put(route('posts.update', $post), [
            'title' => $post->title,
            'content' => $post->content,
            'category_id' => $category->id,
            'tags' => [$replacementTag->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $existingTag->id,
        ]);
        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $replacementTag->id,
        ]);
    }

    public function test_timeline_displays_tags_and_filters_by_one_tag_in_newest_order(): void
    {
        $viewer = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $laravel = Tag::create(['name' => 'Laravel']);
        $php = Tag::create(['name' => 'PHP']);

        $olderMatch = $this->createPost($viewer, $category, '古い一致ポスト');
        $newerMatch = $this->createPost($viewer, $category, '新しい一致ポスト');
        $otherTagPost = $this->createPost($viewer, $category, '別タグのポスト');
        $untaggedPost = $this->createPost($viewer, $category, 'タグなしポスト');

        $olderMatch->tags()->attach($laravel);
        $newerMatch->tags()->attach($laravel);
        $otherTagPost->tags()->attach($php);
        $olderMatch->forceFill(['created_at' => now()->subMinutes(2)])->save();
        $newerMatch->forceFill(['created_at' => now()->subMinute()])->save();

        $this->actingAs($viewer)
            ->get(route('posts.index', ['tag' => $laravel->id]))
            ->assertOk()
            ->assertSeeInOrder(['新しい一致ポスト', '古い一致ポスト'])
            ->assertSee('#Laravel')
            ->assertDontSee('別タグのポスト')
            ->assertDontSee('タグなしポスト');

        $this->assertSame([], $untaggedPost->tags()->pluck('tags.id')->all());
    }

    public function test_timeline_shows_no_tag_markup_for_an_untagged_post(): void
    {
        $viewer = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $this->createPost($viewer, $category, 'タグなしポスト');

        $this->actingAs($viewer)
            ->get(route('posts.index'))
            ->assertOk()
            ->assertSee('タグなしポスト')
            ->assertDontSee('class="post-tags"', false);
    }

    public function test_filter_with_no_matching_posts_shows_an_empty_state_and_can_be_cleared(): void
    {
        $viewer = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $tag = Tag::create(['name' => 'Laravel']);
        $otherTag = Tag::create(['name' => 'PHP']);
        $post = $this->createPost($viewer, $category, 'PHPのポスト');
        $post->tags()->attach($otherTag);

        $this->actingAs($viewer)
            ->get(route('posts.index', ['tag' => $tag->id]))
            ->assertOk()
            ->assertSee('このタグが付いたポストはありません。')
            ->assertDontSee('PHPのポスト')
            ->assertSee(route('posts.index'));
    }

    public function test_invalid_tag_ids_are_rejected_with_a_japanese_message(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $tag = Tag::create(['name' => 'Laravel']);

        $this->actingAs($user)
            ->from(route('posts.create'))
            ->post(route('posts.store'), [
                'title' => '無効なタグのポスト',
                'category_id' => $category->id,
                'content' => '本文',
                'tags' => [9999],
            ])
            ->assertRedirect(route('posts.create'))
            ->assertSessionHasErrors(['tags.0' => '選択したタグが正しくありません']);

        $post = $this->createPost($user, $category, '更新対象');
        $post->tags()->attach($tag);

        $this->from(route('posts.edit', $post))
            ->put(route('posts.update', $post), [
                'title' => $post->title,
                'content' => $post->content,
                'category_id' => $category->id,
                'tags' => [9999],
            ])
            ->assertRedirect(route('posts.edit', $post))
            ->assertSessionHasErrors(['tags.0' => '選択したタグが正しくありません']);

        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_database_seeder_adds_five_tags_distinct_from_topics(): void
    {
        $this->seed();

        $this->assertDatabaseCount('tags', 5);
        $this->assertDatabaseMissing('tags', ['name' => 'お知らせ']);
        $this->assertDatabaseMissing('tags', ['name' => '技術メモ']);
        $this->assertDatabaseMissing('tags', ['name' => '雑記']);
    }

    private function createPost(User $author, Category $category, string $title): Post
    {
        return Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => $title,
            'content' => 'テスト本文',
        ]);
    }
}
