<?php

namespace ChrisHenrique\ModalCrud;

use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use ChrisHenrique\ModalCrud\Livewire\Modal;
use ChrisHenrique\ModalCrud\View\Components\ModalComponent;
use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

class ModalCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $dashboard = $this->app->make(Modal::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'modalcrud');

        $this->publishes([
            __DIR__.'/../config/modal-crud.php' => config_path('modal-crud.php'),
        ], 'modal-config');
 
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/modal-crud'),
        ], 'modal-views');

        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/modal-crud/js'),
        ], 'modal-js');

        Livewire::component('modal', Modal::class);
        Blade::component('modal-component', ModalComponent::class);   
        Livewire::component('modal-crud', ModalCrud::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/modal-crud.php', 'modal-crud');
        $this->app->singleton(Modal::class);
        $this->app->alias(Modal::class, 'modal');
    }
}