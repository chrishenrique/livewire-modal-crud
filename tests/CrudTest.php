<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\Tests\Fixtures\CustomModeModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\HookModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\PlainPostModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\Post;
use ChrisHenrique\ModalCrud\Tests\Fixtures\PostModal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class CrudTest extends TestCase
{
    public function test_create_mode_stores_record_and_closes_modal(): void
    {
        Livewire::test(PostModal::class, ['mode' => 'create'])
            ->set('title', 'Hello World')
            ->call('submit')
            ->assertDispatched('closeModalInBrowser');

        $this->assertDatabaseHas('posts', ['title' => 'Hello World']);
    }

    /**
     * Regression: Component::only() reads its argument's *values* as property
     * names, so handing it the validated `attribute => value` map produced
     * `insert into posts ("Hello World")`.
     */
    public function test_create_mode_stores_validated_data_without_a_fillable_list(): void
    {
        Livewire::test(PlainPostModal::class, ['mode' => 'create'])
            ->set('title', 'Hello World')
            ->set('slug', 'hello-world')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('closeModalInBrowser');

        $this->assertDatabaseHas('posts', [
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);
    }

    public function test_update_mode_persists_validated_data_without_a_fillable_list(): void
    {
        $post = $this->makePost();

        Livewire::test(PlainPostModal::class, ['mode' => 'edit', 'id' => $post->id])
            ->set('title', 'Updated')
            ->set('slug', 'updated')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Updated', $post->fresh()->title);
        $this->assertSame('updated', $post->fresh()->slug);
    }

    public function test_fillable_list_restricts_what_is_persisted(): void
    {
        Livewire::test(PostModal::class, ['mode' => 'create'])
            ->set('title', 'Hello World')
            ->call('submit');

        // `ignored` is a public property but is not in $fillable.
        $this->assertArrayNotHasKey('ignored', Post::first()->getAttributes());
    }

    public function test_create_mode_validates_before_storing(): void
    {
        Livewire::test(PostModal::class, ['mode' => 'create'])
            ->set('title', 'no') // < min:3
            ->call('submit')
            ->assertHasErrors(['title' => 'min']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_edit_mode_hydrates_model_and_updates(): void
    {
        $post = $this->makePost();

        Livewire::test(PostModal::class, ['mode' => 'edit', 'id' => $post->id])
            ->assertSet('title', 'Original')
            ->set('title', 'Updated')
            ->call('submit')
            ->assertDispatched('closeModalInBrowser');

        $this->assertSame('Updated', $post->fresh()->title);
    }

    public function test_edit_mode_does_not_overwrite_edits_on_later_requests(): void
    {
        $post = $this->makePost();

        Livewire::test(PostModal::class, ['mode' => 'edit', 'id' => $post->id])
            ->set('title', 'Typed by the user')
            ->call('$refresh')
            ->assertSet('title', 'Typed by the user');
    }

    public function test_delete_mode_destroys_model(): void
    {
        $post = $this->makePost('Doomed');

        Livewire::test(PostModal::class, ['mode' => 'delete', 'id' => $post->id])
            ->call('submit')
            ->assertDispatched('closeModalInBrowser');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_custom_mode_falls_through_to_action(): void
    {
        Livewire::test(CustomModeModal::class, ['mode' => 'archive'])
            ->assertSet('modalTitle', 'Arquivar')
            ->call('submit')
            ->assertSet('actioned', true)
            ->assertDispatched('closeModalInBrowser');
    }

    public function test_show_mode_hides_the_submit_button(): void
    {
        Livewire::test(PostModal::class, ['mode' => 'show'])
            ->assertSet('showSubmit', false)
            ->assertSet('modalTitle', 'Visualizar')
            ->assertSet('modalClose', 'Voltar')
            ->assertDontSee('wire:click="submit"', false);
    }

    public function test_missing_record_raises_a_model_not_found_exception(): void
    {
        // Laravel 11/12 render the exception as a 404 unless handling is disabled.
        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(PostModal::class, ['mode' => 'edit', 'id' => 999]);
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function hookProvider(): array
    {
        return [
            'create' => ['create', ['beforeValidate', 'afterValidate', 'stored', 'saved']],
            'edit' => ['edit', ['beforeValidate', 'afterValidate', 'changed', 'saved']],
            'delete' => ['delete', ['destroyed', 'saved']],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[DataProvider('hookProvider')]
    public function test_lifecycle_hooks_fire_in_order(string $mode, array $expected): void
    {
        $post = $this->makePost('Original');

        $params = $mode === 'create'
            ? ['mode' => 'create']
            : ['mode' => $mode, 'id' => $post->id];

        Livewire::test(HookModal::class, $params)
            ->set('title', 'Long enough')
            ->call('submit')
            ->assertSet('calls', $expected);
    }

    /**
     * The model used to be re-queried in boot() on every round-trip, on top of
     * the query Livewire's own model hydration already performs.
     */
    public function test_model_is_not_requeried_on_every_request(): void
    {
        $post = $this->makePost();

        $component = Livewire::test(PostModal::class, ['mode' => 'edit', 'id' => $post->id]);

        DB::enableQueryLog();
        $component->call('$refresh');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $selects = array_filter($queries, fn ($q) => str_starts_with(strtolower($q['query']), 'select'));

        $this->assertLessThanOrEqual(
            1,
            count($selects),
            'A plain re-render should cost at most one select for the bound model.'
        );
    }
}
