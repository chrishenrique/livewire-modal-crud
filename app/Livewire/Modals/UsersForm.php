<?php

namespace App\Livewire\Modals;

use App\Models\User;
use App\Livewire\ModalComponent;
use Livewire\Attributes\{Computed,Validate};
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

class UsersForm extends ModalComponent
{
    public string $showTitle = 'Visualizar usuario';
    public string $createTitle = 'Novo usuario';
    public string $editTitle = 'Editar usuario';
    public string $deleteTitle = 'Apagar usuario';
    public string $modalClose = 'Cancelar';
    public string $modalBtn = 'Enviar';

    protected string $view = 'livewire.modals.users-form';
    protected array $fillable = [
        'name',
        'email',
        'password'
    ];

    #[Validate('required|min:3')] 
    public $name;
    #[Validate('required|min:3')] 
    public $email;
    public $password;

    public function mount()
    {
        $this->modalTitle = match ($this->getMode()) {
            'show' => $this->showTitle,
            'create' => $this->createTitle,
            'edit' => $this->editTitle,
            'delete' => $this->deleteTitle,
            default => $this->modalTitle,
        };

        $this->modalBtn = match ($this->getMode()) {
            'delete' => 'Apagar',
            default => $this->modalBtn,
        };

        $this->modalClose = match ($this->getMode()) {
            'show' => 'Voltar',
            default => $this->modalClose,
        };

        $this->id && $this->fill(
            $this->user()
        );

        $this->showSubmit = ($this->getMode() != 'show');
    }

    // #[On('user-saved')] 
    #[Computed()]
    public function user(): User
    {
        return $this->model;
    }
}
