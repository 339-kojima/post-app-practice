<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_post_creation_form_from_timeline(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '技術メモ']);

        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertOk()
            ->assertSee(route('posts.create'))
            ->assertSee('ポストを書く');

        $this->get(route('posts.create'))
            ->assertOk()
            ->assertSee('技術メモ')
            ->assertSee(route('posts.store'));
    }

    public function test_authenticated_user_can_create_a_post_and_becomes_its_author(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::create(['name' => '雑記']);
        $existingPost = Post::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'title' => '既存ポスト',
            'content' => '既存本文',
        ]);
        $existingPost->forceFill([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ])->save();

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => '新しいポスト',
                'category_id' => $category->id,
                'content' => '投稿本文',
                'user_id' => $otherUser->id,
            ])
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => '新しいポスト',
            'content' => '投稿本文',
        ]);

        $createdPost = Post::where('title', '新しいポスト')->firstOrFail();
        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertSeeInOrder(['新しいポスト', '既存ポスト'])
            ->assertSee($user->name)
            ->assertSee(route('posts.edit', $createdPost));
        $this->assertTrue($createdPost->created_at->greaterThanOrEqualTo($existingPost->created_at));
    }

    public function test_post_creation_rejects_invalid_input_and_preserves_old_values(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '雑記']);

        $invalidInputs = [
            [
                'title' => '',
                'category_id' => $category->id,
                'content' => '本文',
                'error' => 'title',
            ],
            [
                'title' => str_repeat('あ', 256),
                'category_id' => $category->id,
                'content' => '本文',
                'error' => 'title',
            ],
            [
                'title' => 'タイトル',
                'category_id' => '',
                'content' => '本文',
                'error' => 'category_id',
            ],
            [
                'title' => 'タイトル',
                'category_id' => 9999,
                'content' => '本文',
                'error' => 'category_id',
            ],
            [
                'title' => 'タイトル',
                'category_id' => $category->id,
                'content' => '',
                'error' => 'content',
            ],
            [
                'title' => 'タイトル',
                'category_id' => $category->id,
                'content' => " \t\n ",
                'error' => 'content',
            ],
        ];

        foreach ($invalidInputs as $input) {
            $error = $input['error'];
            unset($input['error']);

            $this->actingAs($user)
                ->from(route('posts.create'))
                ->post(route('posts.store'), $input)
                ->assertRedirect(route('posts.create'))
                ->assertSessionHasErrors([$error])
                ->assertSessionHasInput('category_id', $input['category_id']);
        }

        $this->assertDatabaseCount('posts', 0);
        $this->actingAs($user)
            ->get(route('posts.create'))
            ->assertSee('value="タイトル"', false);
    }

    public function test_post_creation_routes_require_authentication(): void
    {
        $this->get(route('posts.create'))
            ->assertRedirect(route('login'));

        $this->post(route('posts.store'), [])->assertRedirect(route('login'));
    }
}
