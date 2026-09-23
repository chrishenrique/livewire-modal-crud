<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\ModalCrudServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PublishingTest extends TestCase
{
    /**
     * Regression: views were registered under the `modalcrud` namespace but
     * published to `views/vendor/modal-crud`, a directory Laravel never reads,
     * so `--tag=modal-views` had no effect at all.
     */
    public function test_view_publish_target_matches_the_registered_namespace(): void
    {
        $paths = ServiceProvider::pathsToPublish(ModalCrudServiceProvider::class, 'modal-views');

        $this->assertNotEmpty($paths, 'The modal-views publish tag is not registered.');

        $namespaces = array_keys(View::getFinder()->getHints());

        foreach ($paths as $target) {
            $namespace = basename($target);

            $this->assertContains(
                $namespace,
                $namespaces,
                "Views publish to views/vendor/{$namespace} but no such view namespace is registered."
            );
        }
    }

    public function test_package_views_resolve_under_the_canonical_namespace(): void
    {
        $this->assertTrue(View::exists('modal-crud::livewire.modal'));
        $this->assertTrue(View::exists('modal-crud::livewire.modals.delete'));
        $this->assertTrue(View::exists('modal-crud::components.layouts.modal'));
    }

    public function test_legacy_namespace_still_resolves(): void
    {
        $this->assertTrue(View::exists('modalcrud::livewire.modal'));
    }

    public function test_js_publishes_to_the_directory_the_asset_helper_reads(): void
    {
        $paths = ServiceProvider::pathsToPublish(ModalCrudServiceProvider::class, 'modal-js');

        $this->assertNotEmpty($paths);

        foreach ($paths as $target) {
            $this->assertStringEndsWith('vendor/modal-crud/js', $target);
        }
    }

    public function test_config_publish_tag_is_registered(): void
    {
        $this->assertNotEmpty(
            ServiceProvider::pathsToPublish(ModalCrudServiceProvider::class, 'modal-config')
        );
    }
}
