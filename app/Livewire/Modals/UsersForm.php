<?php

namespace App\Livewire\Modals;

use App\Models\User;
use App\Livewire\ModalComponent;
use Illuminate\Auth\GenericUser;
use Livewire\Attributes\{Computed,Validate};
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

class UsersForm extends ModalComponent
{
    public string $modalTitle = '';
    public string $createTitle = 'Novo usuario';
    public string $editTitle = 'Editar usuario';
    public string $deleteTitle = 'Apagar usuario';
    public $mode;
    public $id;
    public $model;

    #[Validate('required|min:3')] 
    public $name;
    #[Validate('required|min:3')] 
    public $email;
    #[Validate('required|min:3')] 
    public $password;

    public function mount()
    {
        $this->modalTitle = match ($this->mode) {
            'create' => $this->createTitle,
            'edit' => $this->editTitle,
            'delete' => $this->createTitle,
        };

        $this->model && $this->fill((new $this->model)->findOrFail($this->id));
    }

    #[On('user-saved')] 
    #[Computed()]
    public function user(): User
    {
        return match ($this->mode) {
            'create' => new $this->model,
            default => (new $this->model)->findOrFail($this->id),
        };
    }

    public function render()
    {
        if($this->mode === 'delete')
        {
            return <<<'HTML'
            <div>
                {{-- Deseja realmente apagar? --}}
            </div>
            HTML;
        }

        return view('livewire.modals.users-form');
    }

    public function submit(): void
    {
        switch ($this->mode) {
            case 'create':
                $this->store();
                break;
            case 'edit':
                $this->update();
                break;
            case 'delete':
                $this->destroy();
                break;
        }
    }

    protected function store()
    {
        $validateds = $this->validate(); 
        $input = $this->all();
        $this->user->create($input);

        $this->dispatch('user-saved');
        $this->dispatch('user-created');
        $this->closeModal();
    }

    protected function update()
    {
        $validateds = $this->validate(); 
        $input = $this->all();
        $this->user->update($input);

        $this->dispatch('user-saved');
        $this->dispatch('user-updated');
        $this->closeModal();
    }

    public function destroy()
    {
        $this->user->delete();
        $this->dispatch('user-saved');
        $this->dispatch('user-deleted');
        $this->closeModal();
    }
}
