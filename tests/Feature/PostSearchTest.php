<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_searches_title_content_author_and_topic_by_partial_match(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create(['name' => 'Keyword Author']);
        $topic = Category::create(['name' => 'Keyword Topic']);
        $otherTopic = Category::create(['name' => '雑記']);

        $titleMatch = $this->createPost($viewer, $otherTopic, 'Keyword title', '本文');
        $contentMatch = $this->createPost($viewer, $otherTopic, '本文のみ', '本文にKeywordを含む');
        $authorMatch = $this->createPost($author, $otherTopic, '投稿者一致', '本文');
        $topicMatch = $this->createPost($viewer, $topic, 'トピック一致', '本文');
        $unmatched = $this->createPost($viewer, $otherTopic, '一致しない', '別の内容');

        foreach ([$titleMatch, $contentMatch, $authorMatch, $topicMatch] as $post) {
            $response = $this->actingAs($viewer)
                ->get(route('posts.index', ['search' => 'Keyword']))
                ->assertOk();

            $response->assertSee($post->title);
        }

        $this->get(route('posts.index', ['search' => 'Keyword']))
            ->assertDontSee($unmatched->title)
            ->assertSee('name="search" value="Keyword"', false);
    }

    public function test_search_results_are_unique_and_ordered_newest_first(): void
    {
        $user = User::factory()->create();
        $topic = Category::create(['name' => 'Keyword topic']);
        $older = $this->createPost($user, $topic, 'Keyword older', 'Keyword in body');
        $newer = $this->createPost($user, $topic, 'Keyword newer', 'Keyword in body');
        $older->forceFill(['created_at' => now()->subMinutes(2)])->save();
        $newer->forceFill(['created_at' => now()->subMinute()])->save();

        $this->actingAs($user)
            ->get(route('posts.index', ['search' => 'Keyword']))
            ->assertOk()
            ->assertSeeInOrder(['Keyword newer', 'Keyword older'])
            ->assertSee('Keyword newer', false);
    }

    public function test_empty_search_shows_all_posts_in_newest_order(): void
    {
        $user = User::factory()->create();
        $topic = Category::create(['name' => '雑記']);
        $older = $this->createPost($user, $topic, '古いポスト', '本文');
        $newer = $this->createPost($user, $topic, '新しいポスト', '本文');
        $older->forceFill(['created_at' => now()->subMinutes(2)])->save();
        $newer->forceFill(['created_at' => now()->subMinute()])->save();

        $this->actingAs($user)
            ->get(route('posts.index', ['search' => '']))
            ->assertOk()
            ->assertSeeInOrder(['新しいポスト', '古いポスト']);
    }

    public function test_search_without_matches_shows_the_requested_empty_message(): void
    {
        $user = User::factory()->create();
        $topic = Category::create(['name' => '雑記']);
        $this->createPost($user, $topic, 'ポスト', '本文');

        $this->actingAs($user)
            ->get(route('posts.index', ['search' => '存在しない語']))
            ->assertOk()
            ->assertSee('該当するポストはありません');
    }

    public function test_timeline_search_requires_authentication(): void
    {
        $this->get(route('posts.index', ['search' => 'Keyword']))
            ->assertRedirect(route('login'));
    }

    private function createPost(User $author, Category $category, string $title, string $content): Post
    {
        return Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => $title,
            'content' => $content,
        ]);
    }
}
