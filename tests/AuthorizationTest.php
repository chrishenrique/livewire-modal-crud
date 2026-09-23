<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\Tests\Fixtures\AuthorizedModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\PolicyModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\Post;
use ChrisHenrique\ModalCrud\Tests\Fixtures\PostModal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Livewire;
use ReflectionProperty;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Livewire renders through the HTTP kernel, so without this the
        // AuthorizationException is converted into a 403 response instead of
        // reaching the test.
        $this->withoutExceptionHandling();
    }

    public function test_authorize_hook_runs_on_the_initial_request(): void
    {
        $post = $this->makePost('denied');

        $this->expectException(AuthorizationException::class);

        Livewire::test(AuthorizedModal::class, ['mode' => 'edit', 'id' => $post->id]);
    }

    public function test_authorize_hook_runs_again_on_every_subsequent_request(): void
    {
        $post = $this->makePost('allowed');

        $component = Livewire::test(AuthorizedModal::class, ['mode' => 'edit', 'id' => $post->id])
            ->assertOk();

        // The record stops being authorized between requests.
        $post->update(['title' => 'revoked']);

        $this->expectException(AuthorizationException::class);

        $component->call('$refresh');
    }

    public function test_authorized_record_passes_through(): void
    {
        $post = $this->makePost('allowed');

        Livewire::test(AuthorizedModal::class, ['mode' => 'edit', 'id' => $post->id])
            ->assertOk()
            ->assertSet('title', 'allowed');
    }

    /**
     * `mode` and `id` come from the browser via openModal(), so they must not
     * be swappable from the client between requests.
     */
    public function test_mode_and_id_are_locked_against_client_updates(): void
    {
        foreach (['mode', 'id'] as $property) {
            $attributes = (new ReflectionProperty(PostModal::class, $property))
                ->getAttributes(Locked::class);

            $this->assertNotEmpty($attributes, "[\${$property}] must carry #[Locked].");
        }
    }

    /**
     * Exercises the pattern the README documents, end to end through a Gate.
     */
    public function test_documented_policy_pattern_denies_and_allows(): void
    {
        // Nullable user so the gate also runs for guests in this test.
        Gate::define('edit', fn (?Authenticatable $user, Post $post) => $post->title === 'allowed');

        $allowed = $this->makePost('allowed');
        $denied = $this->makePost('denied');

        Livewire::test(PolicyModal::class, ['mode' => 'edit', 'id' => $allowed->id])
            ->assertOk();

        $this->expectException(AuthorizationException::class);

        Livewire::test(PolicyModal::class, ['mode' => 'edit', 'id' => $denied->id]);
    }

    public function test_locked_properties_reject_client_side_updates(): void
    {
        $post = $this->makePost();

        $this->expectException(\Exception::class);

        Livewire::test(PostModal::class, ['mode' => 'show', 'id' => $post->id])
            ->set('mode', 'delete');
    }
}
