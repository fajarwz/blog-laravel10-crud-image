<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseTransactions;

    public function test_posts_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('posts.index'));

        $response->assertOk();
    }

    public function test_posts_page_must_be_accessed_only_by_authenticated_users(): void
    {
        $response = $this
            ->get(route('posts.index'));

        $response
            ->assertRedirect(route('login'));
    }

    public function test_posts_page_must_display_no_data_placeholder_if_the_record_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('posts.index'));

        $response
            ->assertSee('No data can be displayed.');
    }

    public function test_authenticated_users_can_create_a_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->get(route('posts.create'))
            ->assertOk();

        $file = UploadedFile::fake()->image('featured_image.jpg');

        $response = $this
            ->post(route('posts.store'), [
                'title' => $title = fake()->sentence(),
                'content' => fake()->paragraph(),
                'featured_image' => $file,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('posts.index'));

        $response = $this
            ->get(route('posts.index'))
            ->assertSee($title);
    }

    public function test_post_validation_requires_all_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->get(route('posts.create'))
            ->assertOk();

        $response = $this
            ->post(route('posts.store'), []);

        $response
            ->assertSessionHasErrors([
                $attribute = 'title' => $this->getRequiredFieldValidationString($attribute),
                $attribute = 'content' => $this->getRequiredFieldValidationString($attribute),
                $attribute = 'featured_image' => $this->getRequiredFieldValidationString($attribute),
            ])
            ->assertRedirect(route('posts.create'));
    }

    public function test_authenticated_users_can_see_post_details(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->get(route('posts.show', $post));

        $response
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_authenticated_users_can_update_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->get(route('posts.edit', $post));

        $response
            ->assertOk();

        $response = $this
            ->patch(route('posts.update', $post), [
                'title' => $title = fake()->sentence(),
                'content' => $post->content,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('posts.index'));

        $post->refresh();

        $this->assertSame($post->title, $title);

        $response = $this
            ->get(route('posts.index'))
            ->assertSee($title);
    }

    public function test_authenticated_users_can_delete_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->delete(route('posts.destroy', $post));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('posts.index'));

        $this->assertNull($post->fresh());
    }

    private function getRequiredFieldValidationString($attribute)
    {
        return __('validation.required', ['attribute' => str_replace('_', ' ', $attribute)]);
    }
}
