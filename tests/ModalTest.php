<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\Enums\ModalMode;
use ChrisHenrique\ModalCrud\Livewire\Modal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\FakeModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\NotAModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\OptionsModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\TypedPropsModal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Livewire\Livewire;
use ReflectionMethod;

class ModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Livewire::component('fake-modal', FakeModal::class);
        Livewire::component('not-a-modal', NotAModal::class);
        Livewire::component('options-modal', OptionsModal::class);
        Livewire::component('typed-props-modal', TypedPropsModal::class);
    }

    public function test_modal_component_can_render(): void
    {
        Livewire::test(Modal::class)
            ->assertOk()
            ->assertSet('component', null);
    }

    public function test_open_modal_resolves_class_and_renders_nested_component(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'])
            ->assertOk()
            ->assertSee('Novo')   // createTitle, set by ModalCrud::boot()
            ->assertSee('Salvar') // default submit button label
            ->assertSee('modal-lg', false); // modalSize() applied to the dialog
    }

    public function test_open_modal_dispatches_browser_event(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'])
            ->assertDispatched('openModalInBrowser');
    }

    public function test_open_modal_rejects_component_without_contract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement');

        Livewire::test(Modal::class)->call('openModal', 'not-a-modal');
    }

    public function test_open_modal_rejects_unknown_component(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve a class-based Livewire component');

        Livewire::test(Modal::class)->call('openModal', 'does-not-exist');
    }

    public function test_open_modal_is_reachable_via_dispatched_event(): void
    {
        Livewire::test(Modal::class)
            ->dispatch('openModal', 'fake-modal', ['mode' => 'create'])
            ->assertOk()
            ->assertSee('Novo');
    }

    /**
     * The descriptor is cleared on every dehydration on purpose; the nested
     * component owns its lifecycle once mounted.
     */
    public function test_open_modal_clears_descriptor_on_dehydrate(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'])
            ->assertSet('component', null)
            ->assertSee('Novo');
    }

    public function test_modal_options_reach_the_rendered_markup(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'options-modal', ['mode' => 'create'])
            ->assertSee('modal-xl', false)
            ->assertDontSee('modal-dialog-centered', false)
            ->assertDontSee('modal-dialog-scrollable', false)
            ->assertSee('data-close-on-click-away="false"', false)
            ->assertSee('data-close-on-escape="false"', false)
            ->assertSee('data-destroy-on-close="false"', false)
            ->assertSee('data-dispatch-close-event="false"', false);
    }

    public function test_default_modal_options_reach_the_rendered_markup(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'])
            ->assertSee('modal-dialog-centered', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('data-close-on-click-away="true"', false)
            ->assertSee('data-destroy-on-close="true"', false);
    }

    public function test_modal_attributes_argument_overrides_the_static_defaults(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'], [
                'size' => 'modal-sm',
                'centered' => false,
                'closeOnEscape' => false,
            ])
            ->assertSee('modal-sm', false)
            ->assertDontSee('modal-dialog-centered', false)
            ->assertSee('data-close-on-escape="false"', false);
    }

    public function test_destroy_component_clears_the_modal(): void
    {
        Livewire::test(Modal::class)
            ->call('openModal', 'fake-modal', ['mode' => 'create'])
            ->call('destroyComponent')
            ->assertSet('component', null)
            ->assertDontSee('Novo');
    }

    public function test_typed_model_property_is_route_model_bound(): void
    {
        $post = $this->makePost('Bound');

        $modal = Livewire::test(Modal::class)
            ->call('openModal', 'typed-props-modal', ['mode' => 'show', 'post' => $post->id]);

        $modal->assertOk();
    }

    public function test_typed_enum_property_is_resolved(): void
    {
        $component = new Modal;

        $resolved = $component->resolveComponentProps(
            ['resolvedMode' => 'edit'],
            new TypedPropsModal
        );

        $this->assertSame(ModalMode::Edit, $resolved['resolvedMode']);
    }

    /**
     * A typed property that is neither a model nor an enum used to be sent to
     * resolveRouteBinding() and blow up with an unrelated error.
     */
    public function test_typed_non_model_property_is_left_untouched(): void
    {
        $component = new Modal;

        $resolved = $component->resolveComponentProps(
            ['date' => '2026-01-01'],
            new TypedPropsModal
        );

        $this->assertSame('2026-01-01', $resolved['date']);
    }

    public function test_unknown_bound_model_raises_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new Modal)->resolveComponentProps(['post' => 999], new TypedPropsModal);
    }

    /**
     * Key generation used serialize(), which throws on closures. It now
     * degrades to a random key instead of aborting the whole openModal call.
     *
     * (Livewire itself still rejects arguments it cannot serialize into the
     * component payload; this only guarantees the key is never the failure.)
     */
    public function test_component_key_survives_unserializable_arguments(): void
    {
        $key = (new ReflectionMethod(Modal::class, 'componentKey'))
            ->invoke(new Modal, 'fake-modal', ['callback' => fn () => null]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
    }

    public function test_component_key_is_stable_for_identical_arguments(): void
    {
        $method = new ReflectionMethod(Modal::class, 'componentKey');
        $modal = new Modal;

        $this->assertSame(
            $method->invoke($modal, 'fake-modal', ['mode' => 'edit', 'id' => 1]),
            $method->invoke($modal, 'fake-modal', ['mode' => 'edit', 'id' => 1])
        );

        $this->assertNotSame(
            $method->invoke($modal, 'fake-modal', ['mode' => 'edit', 'id' => 1]),
            $method->invoke($modal, 'fake-modal', ['mode' => 'edit', 'id' => 2])
        );
    }
}
