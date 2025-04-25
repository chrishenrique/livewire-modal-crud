<?php

namespace ChrisHenrique\ModalCrud\Livewire;

use InvalidArgumentException;
use Livewire\Component;
use ChrisHenrique\ModalCrud\Livewire\Contracts\ModalContract;

abstract class ModalCrud extends Component implements ModalContract
{
    public bool $forceClose = false;
    public int $skipModals = 0;
    public bool $destroySkipped = false;

    public $mode = null;
    public $id = null;
    public $model = null;
    protected $modelClass = null;
    public $showSubmit = true;

    public string $viewForm = '';
    public string $modalTitle = '';
    public string $showTitle = 'Visualizar';
    public string $createTitle = 'Novo';
    public string $editTitle = 'Editar';
    public string $deleteTitle = 'Apagar';
    public string $deleteBtn = 'Apagar';
    public string $modalClose = 'Cancelar';
    public string $modalBtn = 'Salvar';

    function getMode()
    {
        return $this->mode;
    }

    public function boot()
    {
        $this->modelClass && $this->model = new $this->modelClass;

        if($this->id)
        {
            $this->model = $this->model->findOrFail($this->id);
            $this->fill($this->model);
        }

        $this->showSubmit = ($this->getMode() != 'show');

        $this->modalTitle = match ($this->getMode()) {
            'show' => $this->showTitle,
            'create' => $this->createTitle,
            'edit' => $this->editTitle,
            'delete' => $this->deleteTitle,
            default => $this->modalTitle,
        };

        $this->modalBtn = match ($this->getMode()) {
            'delete' => $this->deleteBtn,
            default => $this->modalBtn,
        };

        $this->modalClose = match ($this->getMode()) {
            'show' => 'Voltar',
            default => $this->modalClose,
        };
    }

    public function render()
    {
        return match ($this->getMode()) {
            'create' => $this->viewCreate(),
            'edit' => $this->viewEdit(),
            'delete' => $this->viewDelete(),
            'show' => $this->viewShow(),
            default => view($this->viewForm ?? 'modalcrud::components.layouts.modal'),
        };
    }

    public function submit(): void
    {
        switch ($this->getMode()) {
            case 'create':
                $this->store();
                break;
            case 'edit':
                $this->update();
                break;
            case 'delete':
                $this->destroy();
                break;
            default:
                $this->action();
                break;
        }
    }

    protected function store(): void
    {
        $this->beforeValidate();
        $validateds = $this->validate(); 
        $this->afterValidate();
        $this->model && $this->model = $this->model->create($this->only($this->fillable ?? $validateds));
        $this->closeModal();
        $this->emitEvent();
    }

    protected function update(): void
    {
        $this->beforeValidate();
        $validateds = $this->validate(); 
        $this->afterValidate();
        $this->model && $this->model->update($this->only($this->fillable ?? $validateds));
        $this->closeModal();
        $this->emitEvent();
    }

    protected function destroy(): void
    {
        $this->model && $this->model->delete();
        $this->closeModal();
        $this->emitEvent();
    }

    protected function action(): void
    {
        $this->closeModal();
        $this->emitEvent();
    }

    public static function modalSize(): string
    {
        /*
            modal-sm
            modal-md
            modal-lg
            modal-xl
            modal-fullscreen
            modal-fullscreen-sm-down	Below 576px
            modal-fullscreen-md-down	Below 768px
            modal-fullscreen-lg-down	Below 992px
            modal-fullscreen-xl-down	Below 1200px
            modal-fullscreen-xxl-down   Below 1400px
         */
        return '';
    }

    public function destroySkippedModals(): self
    {
        $this->destroySkipped = true;

        return $this;
    }

    public function skipPreviousModals($count = 1, $destroy = false): self
    {
        $this->skipPreviousModal($count, $destroy);

        return $this;
    }

    public function skipPreviousModal($count = 1, $destroy = false): self
    {
        $this->skipModals = $count;
        $this->destroySkipped = $destroy;

        return $this;
    }

    public function forceClose(): self
    {
        $this->forceClose = true;

        return $this;
    }

    public function closeModal(): void
    {
        $this->dispatch('closeModalInBrowser', force: $this->forceClose, skipPreviousModals: $this->skipModals, destroySkipped: $this->destroySkipped);
    }

    public function closeModalWithEvents(array $events): void
    {
        $this->emitModalEvents($events);
        $this->closeModal();
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

    public static function closeModalOnEscapeIsForceful(): bool
    {
        return true;
    }

    public static function dispatchCloseEvent(): bool
    {
        return true;
    }

    public static function destroyOnClose(): bool
    {
        return true;
    }

    private function emitModalEvents(array $events): void
    {
        foreach ($events as $component => $event) {
            if (is_array($event)) {
                [$event, $params] = $event;
            }

            if (is_numeric($component)) {
                $this->dispatch($event, ...$params ?? []);
            } else {
                $this->dispatch($event, ...$params ?? [])->to($component);
            }
        }
    }

    public function viewShow()
    {
        return view($this->viewForm ?? 'modalcrud::components.layouts.modal');
    }

    public function viewCreate()
    {
        return view($this->viewForm ?? 'modalcrud::components.layouts.modal');
    }

    public function viewEdit()
    {
        return view($this->viewForm ?? 'modalcrud::components.layouts.modal');
    }

    public function viewDelete()
    {
        return view('modalcrud::livewire.modals.delete')
                    ->with('message', 'Deseja realmente apagar?');
    }

    public function emitEvent(): void
    {
        $method = match ($this->getMode()) {
            'create' => 'stored',
            'edit' => 'updated',
            'delete' => 'destroyed',
            default => null,
        };

        $method && method_exists($this, $method) && $this->$method();
        method_exists($this, 'saved') && $this->saved();
    }

    public function beforeValidate(): void
    {
    }

    public function afterValidate(): void
    {
    }

}