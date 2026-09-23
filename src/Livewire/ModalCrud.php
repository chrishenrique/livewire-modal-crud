<?php

namespace ChrisHenrique\ModalCrud\Livewire;

use ChrisHenrique\ModalCrud\Enums\ModalMode;
use ChrisHenrique\ModalCrud\Livewire\Contracts\ModalContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class ModalCrud extends Component implements ModalContract
{
    /**
     * The mode drives the title, the view and what `submit()` does.
     *
     * Locked because it arrives from the browser through `openModal()` and must
     * not be swappable between requests — otherwise a modal opened in `show`
     * mode could be flipped to `delete` from the client.
     */
    #[Locked]
    public ?string $mode = null;

    /**
     * Primary key of the record being edited/shown/deleted, or null on create.
     */
    #[Locked]
    public mixed $id = null;

    public ?Model $model = null;

    /**
     * Eloquent model backing this modal. Leave null for modals without a record.
     *
     * Left untyped so subclasses can keep declaring `protected $modelClass`.
     *
     * @var class-string<Model>|null
     */
    protected $modelClass = null;

    /**
     * Attribute names persisted by store()/update().
     *
     * Leave empty to persist the validated data as-is, which is what most
     * modals want.
     *
     * @var array<int, string>
     */
    public array $fillable = [];

    public bool $showSubmit = true;

    public string $viewForm = '';

    public string $modalTitle = '';

    public string $showTitle = 'Visualizar';

    public string $createTitle = 'Novo';

    public string $editTitle = 'Editar';

    public string $deleteTitle = 'Apagar';

    public string $deleteMessage = 'Deseja realmente apagar?';

    public string $deleteBtn = 'Apagar';

    public string $modalClose = 'Cancelar';

    public string $showClose = 'Voltar';

    public string $modalBtn = 'Salvar';

    public function getMode(): ?string
    {
        return $this->mode;
    }

    /**
     * The resolved built-in mode, or null when a custom mode string is in use.
     */
    public function getModalMode(): ?ModalMode
    {
        return ModalMode::parse($this->getMode());
    }

    /**
     * Authorization gate for this modal.
     *
     * `mode` and `id` are supplied by the browser, so every modal that reads or
     * writes a record MUST override this. The default is a no-op only so that
     * modals without a model keep working; it is not a safe default for data.
     *
     * Throw (e.g. `$this->authorize('update', $this->model)`) to deny access.
     */
    protected function authorizeModal(): void
    {
        //
    }

    /**
     * Whether the submit button is rendered. Override to change the rule.
     */
    protected function showSubmit(): bool
    {
        return $this->getModalMode() !== ModalMode::Show;
    }

    public function boot(): void
    {
        $this->showSubmit = $this->showSubmit();

        $this->modalTitle = match ($this->getModalMode()) {
            ModalMode::Show => $this->showTitle,
            ModalMode::Create => $this->createTitle,
            ModalMode::Edit => $this->editTitle,
            ModalMode::Delete => $this->deleteTitle,
            default => $this->modalTitle,
        };

        $this->modalBtn = match ($this->getModalMode()) {
            ModalMode::Delete => $this->deleteBtn,
            default => $this->modalBtn,
        };

        $this->modalClose = match ($this->getModalMode()) {
            ModalMode::Show => $this->showClose,
            default => $this->modalClose,
        };
    }

    public function mount(): void
    {
        $this->resolveModel();

        // Fill the form from the model only on the initial load. Doing this on
        // every request would overwrite the user's edits with the stale
        // database values before submit() runs.
        if ($this->id && $this->model) {
            $this->fill($this->model);
        }
    }

    /**
     * Runs after mount() on the first request and after hydrate() on every
     * subsequent one, which is exactly where the model is guaranteed to be
     * available — either freshly resolved or restored by Livewire.
     */
    public function booted(): void
    {
        $this->resolveModel();
        $this->authorizeModal();
    }

    /**
     * Idempotent: skips the query when Livewire already rehydrated the model.
     */
    protected function resolveModel(): void
    {
        if ($this->modelClass === null) {
            return;
        }

        if ($this->model instanceof $this->modelClass) {
            return;
        }

        $this->model = $this->id
            ? (new $this->modelClass)->findOrFail($this->id)
            : new $this->modelClass;
    }

    public function render(): View
    {
        return match ($this->getModalMode()) {
            ModalMode::Create => $this->viewCreate(),
            ModalMode::Edit => $this->viewEdit(),
            ModalMode::Delete => $this->viewDelete(),
            ModalMode::Show => $this->viewShow(),
            default => $this->viewDefault(),
        };
    }

    public function submit(): void
    {
        match ($this->getModalMode()) {
            ModalMode::Create => $this->store(),
            ModalMode::Edit => $this->update(),
            ModalMode::Delete => $this->destroy(),
            default => $this->action(),
        };
    }

    protected function store(): void
    {
        $this->beforeValidate();
        $validated = $this->validate();
        $this->afterValidate();

        if ($this->model) {
            $this->model = $this->model->create($this->persistableData($validated));
        }

        $this->closeModal();
        $this->emitEvent();
    }

    protected function update(): void
    {
        $this->beforeValidate();
        $validated = $this->validate();
        $this->afterValidate();

        $this->model?->update($this->persistableData($validated));

        $this->closeModal();
        $this->emitEvent();
    }

    protected function destroy(): void
    {
        $this->model?->delete();

        $this->closeModal();
        $this->emitEvent();
    }

    protected function action(): void
    {
        $this->closeModal();
        $this->emitEvent();
    }

    /**
     * Build the attribute payload for create()/update().
     *
     * `$validated` is already an `attribute => value` map, so it is used as-is.
     * `Component::only()` treats its argument's *values* as property names, so
     * it may only be handed the `$fillable` list of names.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function persistableData(array $validated): array
    {
        return $this->fillable === []
            ? $validated
            : $this->only($this->fillable);
    }

    public function closeModal(): void
    {
        $this->dispatch('closeModalInBrowser');
    }

    /**
     * @param  array<int|string, string|array{0: string, 1: array<string, mixed>}>  $events
     */
    public function closeModalWithEvents(array $events): void
    {
        $this->emitModalEvents($events);
        $this->closeModal();
    }

    /**
     * @param  array<int|string, string|array{0: string, 1: array<string, mixed>}>  $events
     */
    private function emitModalEvents(array $events): void
    {
        foreach ($events as $component => $event) {
            // Reset per iteration: leaving $params from a previous entry would
            // silently pass one event's payload to the next.
            $params = [];

            if (is_array($event)) {
                [$event, $params] = $event;
            }

            is_numeric($component)
                ? $this->dispatch($event, ...$params)
                : $this->dispatch($event, ...$params)->to($component);
        }
    }

    public static function modalSize(): string
    {
        /*
            modal-sm
            modal-md
            modal-lg
            modal-xl
            modal-fullscreen
            modal-fullscreen-sm-down    Below 576px
            modal-fullscreen-md-down    Below 768px
            modal-fullscreen-lg-down    Below 992px
            modal-fullscreen-xl-down    Below 1200px
            modal-fullscreen-xxl-down   Below 1400px
         */
        return '';
    }

    public static function modalCentered(): bool
    {
        return true;
    }

    public static function modalScrollable(): bool
    {
        return true;
    }

    public static function closeModalOnClickAway(): bool
    {
        return true;
    }

    public static function closeModalOnEscape(): bool
    {
        return true;
    }

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public static function dispatchCloseEvent(): bool
    {
        return true;
    }

    public function viewShow(): View
    {
        return $this->viewDefault();
    }

    public function viewCreate(): View
    {
        return $this->viewDefault();
    }

    public function viewEdit(): View
    {
        return $this->viewDefault();
    }

    public function viewDelete(): View
    {
        return $this->viewFrom('modal-crud::livewire.modals.delete')
            ->with('message', $this->deleteMessage);
    }

    protected function viewDefault(): View
    {
        return $this->viewFrom($this->viewForm ?: 'modal-crud::components.layouts.modal');
    }

    public function emitEvent(): void
    {
        $method = match ($this->getModalMode()) {
            ModalMode::Create => 'stored',
            ModalMode::Edit => 'changed',
            ModalMode::Delete => 'destroyed',
            default => null,
        };

        $method && method_exists($this, $method) && $this->{$method}();
        method_exists($this, 'saved') && $this->saved();
    }

    public function beforeValidate(): void
    {
        //
    }

    public function afterValidate(): void
    {
        //
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
