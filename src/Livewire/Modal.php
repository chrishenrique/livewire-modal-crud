<?php

namespace ChrisHenrique\ModalCrud\Livewire;

use Exception;
use ReflectionClass;
use Livewire\Component;
use Illuminate\View\View;
use Illuminate\Support\Reflector;
use Illuminate\Support\Collection;
use Livewire\Mechanisms\ComponentRegistry;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ChrisHenrique\ModalCrud\Livewire\Contracts\ModalContract;

/* Libs Based
https://github.com/wire-elements/modal
https://github.com/scchethu/Bootstap-4-livewire-modal
*/

class Modal extends Component
{
    // public ?string $activeComponent;

    // public array $components = [];
    public $component = null;
    public string $libPath = '';

    public function __construct()
    {
        $this->libPath = (new \ChrisHenrique\ModalCrud\Modal)->getJsPath();
    }

    public function resetState(): void
    {
        // $this->components = [];
        // $this->activeComponent = null;
    }

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $requiredInterface = ModalContract::class;
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

    public function render()
    {
        return view('modalcrud::livewire.modal');
    }

}