<?php

namespace App\Livewire;

use InvalidArgumentException;
use Livewire\Component;
use App\Livewire\Contracts\ModalComponent as Contract;

abstract class ModalComponent extends Component implements Contract
{
    public bool $forceClose = false;
    public int $skipModals = 0;
    public bool $destroySkipped = false;

    public $mode = null;
    public $id = null;
    public $model = null;
    public $showSubmit = true;

    protected string $view = '';
    public string $modalTitle = '';
    public string $showTitle = 'Visualizar';
    public string $createTitle = 'Novo';
    public string $editTitle = 'Editar';
    public string $deleteTitle = 'Apagar';
    public string $modalClose = 'Cancelar';
    public string $modalBtn = 'Salvar';

    function getMode()
    {
        return $this->mode;
    }

    public function boot()
    {
        $this->model = new $this->model;

        if($this->id)
        {
            $this->model = $this->model->findOrFail($this->id);
        }
    }

    public function render()
    {
        if($this->getMode() === 'delete')
        {
            return view('livewire.modals.delete')
                    ->with('message', 'Deseja realmente apagar?');
        }

        return view($this->view);
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
            case 'show':
                break;
        }
    }

    protected function store()
    {
        $validateds = $this->validate(); 
        $this->model->create($this->only($this->fillable ?? $validateds));
        $this->reset(); 
        $this->closeModal();
    }

    protected function update()
    {
        $validateds = $this->validate(); 
        $this->model->update($this->only($this->fillable ?? $validateds));
        $this->reset(); 
        $this->closeModal();
    }

    protected function destroy()
    {
        $this->model->delete();
        $this->reset(); 
        $this->closeModal();
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

}