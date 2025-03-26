<?php

namespace App\Livewire;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;
use ReflectionClass;

/* Libs Based
https://github.com/wire-elements/modal
https://github.com/scchethu/Bootstap-4-livewire-modal
*/

class Modal extends Component
{
    // public ?string $activeComponent;

    // public array $components = [];
    public $component = null;

    public function resetState(): void
    {
        // $this->components = [];
        // $this->activeComponent = null;
    }

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $requiredInterface = \App\Livewire\Contracts\ModalComponent::class;
        $componentClass = app(ComponentRegistry::class)->getClass($component);
        $reflect = new ReflectionClass($componentClass);

        if ($reflect->implementsInterface($requiredInterface) === false) {
            throw new Exception("[{$componentClass}] does not implement [{$requiredInterface}] interface.");
        }

        $id = md5($component.serialize($arguments));

        $arguments = collect($arguments)
            ->merge($this->resolveComponentProps($arguments, new $componentClass()))
            ->all();

        $this->component = [
            'id' => $id,
            'name' => $component,
            'attributes' => $arguments, // Deprecated
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'closeOnClickAway' => $componentClass::closeModalOnClickAway(),
                'closeOnEscape' => $componentClass::closeModalOnEscape(),
                'closeOnEscapeIsForceful' => $componentClass::closeModalOnEscapeIsForceful(),
                'dispatchCloseEvent' => $componentClass::dispatchCloseEvent(),
                'destroyOnClose' => $componentClass::destroyOnClose(),
                'size' => $componentClass::modalSize(),
                'scrollable' => $componentClass::modalScrollable(),
                'centered' => $componentClass::modalCentered(),
            ], $modalAttributes),
        ];

        // $this->components[$id] = $this->component;

        // $this->activeComponent = $id;
        $this->dispatch('openModalInBrowser');
        // $this->dispatch('activeModalComponentChanged', id: $id);
    }

    public function resolveComponentProps(array $attributes, Component $component): Collection
    {
        return $this->getPublicPropertyTypes($component)
            ->intersectByKeys($attributes)
            ->map(function ($className, $propName) use ($attributes) {
                $resolved = $this->resolveParameter($attributes, $propName, $className);

                return $resolved;
            });
    }

    protected function resolveParameter($attributes, $parameterName, $parameterClassName)
    {
        $parameterValue = $attributes[$parameterName];

        if ($parameterValue instanceof UrlRoutable) {
            return $parameterValue;
        }

        if(enum_exists($parameterClassName)){
            $enum = $parameterClassName::tryFrom($parameterValue);
        
            if($enum !== null){
                return $enum;
            }
        }

        $instance = app()->make($parameterClassName);

        if (! $model = $instance->resolveRouteBinding($parameterValue)) {
            throw (new ModelNotFoundException())->setModel(get_class($instance), [$parameterValue]);
        }

        return $model;
    }

    public function getPublicPropertyTypes($component): Collection
    {
        return collect($component->all())
            ->map(function ($value, $name) use ($component) {
                return Reflector::getParameterClassName(new \ReflectionProperty($component, $name));
            })
            ->filter();
    }

    public function destroyComponent(): void
    {
        // unset($this->components[$id]);
        $this->component = null;
    }

    public function getListeners(): array
    {
        return [
            'openModal',
            'destroyComponent',
        ];
    }

    public function dehydrate()
    {
        $this->component = null;
    }

}