<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\Facades\Modal as ModalFacade;
use ChrisHenrique\ModalCrud\ModalAssets;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class AssetsTest extends TestCase
{
    public function test_facade_binding_resolves_the_asset_helper(): void
    {
        $this->assertInstanceOf(ModalAssets::class, app('modal'));
        $this->assertInstanceOf(ModalAssets::class, app(ModalAssets::class));
    }

    public function test_facade_alias_points_at_the_facade_not_the_concrete_class(): void
    {
        // Regression: the composer alias used to expose the concrete class, so
        // any static call failed with "non-static method called statically".
        $this->assertSame('bootstrap5', ModalFacade::lib());
    }

    public function test_lib_comes_from_config(): void
    {
        config()->set('modal-crud.lib', 'bootstrap4');

        $this->assertSame('bootstrap4', app(ModalAssets::class)->lib());
    }

    public function test_unsupported_lib_is_rejected(): void
    {
        config()->set('modal-crud.lib', '../../etc/passwd');

        $this->expectException(InvalidArgumentException::class);

        app(ModalAssets::class)->lib();
    }

    public function test_core_cannot_be_selected_as_the_lib(): void
    {
        config()->set('modal-crud.lib', 'core');

        $this->expectException(InvalidArgumentException::class);

        app(ModalAssets::class)->lib();
    }

    public function test_scripts_are_core_then_adapter(): void
    {
        $scripts = app(ModalAssets::class)->scripts();

        $this->assertCount(2, $scripts);
        $this->assertStringContainsString('core', $scripts[0]);
        $this->assertStringContainsString('bootstrap5', $scripts[1]);
    }

    /**
     * Regression: the fallback path pointed at public/vendor/chrishenrique/...,
     * which nothing ever creates, so an unpublished install 404'd silently.
     */
    public function test_unpublished_assets_resolve_to_the_package_files(): void
    {
        $assets = app(ModalAssets::class);

        foreach (ModalAssets::SCRIPTS as $script) {
            $this->assertFileExists($assets->path($script));
        }
    }

    public function test_asset_route_serves_the_javascript(): void
    {
        $this->get(route('modal-crud.asset', ['script' => 'core']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=utf-8')
            ->assertSee('ModalCrud', false);

        $this->get(route('modal-crud.asset', ['script' => 'bootstrap5']))
            ->assertOk()
            ->assertSee('bootstrap', false);
    }

    public function test_asset_route_rejects_unknown_scripts(): void
    {
        $this->get('/modal-crud/evil.js')->assertNotFound();
    }

    public function test_asset_urls_are_cache_busted(): void
    {
        $this->assertStringContainsString('id=', app(ModalAssets::class)->url('core'));
    }

    /**
     * @assets content is hoisted out of the component body into the page, so
     * it only shows up on a full page render.
     */
    public function test_rendered_page_includes_both_scripts(): void
    {
        Route::get('/modal-page', fn () => Blade::render(
            '<html><body>@livewire("modal")@livewireScripts</body></html>'
        ));

        $response = $this->get('/modal-page')->assertOk();

        $response->assertSee('/modal-crud/core.js', false);
        $response->assertSee('/modal-crud/bootstrap5.js', false);
        $response->assertSee('defer', false);
    }
}
