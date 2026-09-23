<?php

namespace ChrisHenrique\ModalCrud\Livewire;

use BackedEnum;
use ChrisHenrique\ModalCrud\Livewire\Contracts\ModalContract;
use ChrisHenrique\ModalCrud\ModalAssets;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Component;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/* Libs Based
https://github.com/wire-elements/modal
https://github.com/scchethu/Bootstap-4-livewire-modal
*/

class Modal extends Component
{
    /**
     * The modal currently mounted, or null when nothing is open.
     *
     * @var array{id: string, name: string, arguments: array<string, mixed>, modalAttributes: array<string, mixed>}|null
     */
    public ?array $component = null;

    /**
     * Mount a Livewire component inside the modal shell.
     *
     * SECURITY: this listener is reachable from the browser, so `$component`,
     * `$arguments` and `$modalAttributes` are all untrusted input. The
     * ModalContract check below is a routing guard, not an authorization one —
     * each modal is responsible for its own access control (see
     * ModalCrud::authorizeModal()).
     *
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $modalAttributes
     */
    public function openModal(string $component, array $arguments = [], array $modalAttributes = []): void
    {
        $componentClass = app('livewire.finder')->resolveClassComponentClassName($component);

        if ($componentClass === null) {
            throw new InvalidArgumentException("Unable to resolve a class-based Livewire component for [{$component}].");
        }

        $reflect = new ReflectionClass($componentClass);

        if ($reflect->implementsInterface(ModalContract::class) === false) {
            throw new InvalidArgumentException(
                "[{$componentClass}] does not implement [".ModalContract::class.'] interface.'
            );
        }

        $arguments = collect($arguments)
            ->merge($this->resolveComponentProps($arguments, new $componentClass))
            ->all();

        $this->component = [
            'id' => $this->componentKey($component, $arguments),
            'name' => $component,
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'closeOnClickAway' => $componentClass::closeModalOnClickAway(),
                'closeOnEscape' => $componentClass::closeModalOnEscape(),
                'destroyOnClose' => $componentClass::destroyOnClose(),
                'dispatchCloseEvent' => $componentClass::dispatchCloseEvent(),
                'size' => $componentClass::modalSize(),
                'scrollable' => $componentClass::modalScrollable(),
                'centered' => $componentClass::modalCentered(),
            ], $modalAttributes),
        ];

        $this->dispatch('openModalInBrowser');
    }

    /**
     * Stable key for the nested component.
     *
     * json_encode is used instead of serialize() so that non-serializable
     * arguments (closures, resources) cannot blow up modal opening; anything
     * json_encode cannot express falls back to a random key, which only costs
     * a re-mount.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function componentKey(string $component, array $arguments): string
    {
        $signature = json_encode($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

        return md5($component.($signature === false ? uniqid('', true) : $signature));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return Collection<string, mixed>
     */
    public function resolveComponentProps(array $attributes, Component $component): Collection
    {
        return $this->getPublicPropertyTypes($component)
            ->intersectByKeys($attributes)
            ->map(fn ($className, $propName) => $this->resolveParameter($attributes, $propName, $className));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveParameter(array $attributes, string $parameterName, string $parameterClassName): mixed
    {
        $parameterValue = $attributes[$parameterName];

        if ($parameterValue instanceof UrlRoutable) {
            return $parameterValue;
        }

        // Only backed enums expose tryFrom(); a pure enum would fatal here.
        if (is_subclass_of($parameterClassName, BackedEnum::class)) {
            return is_int($parameterValue) || is_string($parameterValue)
                ? $parameterClassName::tryFrom($parameterValue) ?? $parameterValue
                : $parameterValue;
        }

        // Only Eloquent models take part in route-model binding. Without this
        // guard any typed public property (Carbon, a DTO, a value object) would
        // reach resolveRouteBinding() and fail with an unrelated error.
        if (! is_subclass_of($parameterClassName, Model::class)) {
            return $parameterValue;
        }

        $instance = app()->make($parameterClassName);

        if (! $model = $instance->resolveRouteBinding($parameterValue)) {
            throw (new ModelNotFoundException)->setModel($parameterClassName, [$parameterValue]);
        }

        return $model;
    }

    /**
     * Class names of the component's typed public properties, keyed by name.
     *
     * @return Collection<string, class-string>
     */
    public function getPublicPropertyTypes(Component $component): Collection
    {
        $types = [];

        foreach (array_keys($component->all()) as $name) {
            $className = $this->propertyClassName(new ReflectionProperty($component, (string) $name));

            if ($className !== null) {
                $types[(string) $name] = $className;
            }
        }

        return collect($types);
    }

    /**
     * The declared class of a typed property, or null for builtin/untyped ones.
     */
    /**
     * @return class-string|null
     */
    protected function propertyClassName(ReflectionProperty $property): ?string
    {
        $type = $property->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    public function destroyComponent(): void
    {
        $this->component = null;
    }

    /**
     * @return array<int, string>
     */
    public function getListeners(): array
    {
        return [
            'openModal',
            'destroyComponent',
        ];
    }

    /**
     * The mounted component is intentionally dropped on every dehydration.
     *
     * The nested Livewire component takes over its own lifecycle once mounted,
     * so keeping the descriptor in this component's payload would only make it
     * re-mount (and reset) on every parent round-trip. Removing this line
     * breaks the modal — see ModalTest::test_open_modal_clears_descriptor_on_dehydrate.
     */
    public function dehydrate(): void
    {
        $this->component = null;
    }

    public function render(): View
    {
        return $this->viewFrom('modal-crud::livewire.modal', [
            'assets' => app(ModalAssets::class),
        ]);
    }

    /**
     * Render a view by name.
     *
     * The view factory is used instead of the `view()` helper because these
     * names are runtime values (a package namespace, or a user-supplied
     * `$viewForm`) and can never satisfy a statically verified view-string.
     *
     * @param  array<string, mixed>  $data
     */
    protected function viewFrom(string $name, array $data = []): View
    {
        return app(ViewFactory::class)->make($name, $data);
    }
}
