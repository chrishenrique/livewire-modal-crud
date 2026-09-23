<?php

namespace ChrisHenrique\ModalCrud;

use ChrisHenrique\ModalCrud\Http\Controllers\AssetController;
use ChrisHenrique\ModalCrud\Livewire\Modal;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModalCrudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/modal-crud.php', 'modal-crud');

        $this->app->singleton(ModalAssets::class);
        $this->app->alias(ModalAssets::class, 'modal');
    }

    public function boot(): void
    {
        // The namespace must match the publish directory below: Laravel only
        // looks for view overrides in `views/vendor/{namespace}`.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'modal-crud');

        // Deprecated alias kept for 1.x compatibility; removed in 3.0.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'modalcrud');

        $this->registerRoutes();

        Livewire::component('modal', Modal::class);

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    protected function registerRoutes(): void
    {
        // A controller class (rather than a closure) keeps `route:cache` working.
        Route::get(
            trim((string) config('modal-crud.asset_route_prefix', 'modal-crud'), '/').'/{script}.js',
            AssetController::class
        )->name('modal-crud.asset');
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/modal-crud.php' => config_path('modal-crud.php'),
        ], 'modal-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/modal-crud'),
        ], 'modal-views');

        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/modal-crud/js'),
        ], 'modal-js');
    }
}
