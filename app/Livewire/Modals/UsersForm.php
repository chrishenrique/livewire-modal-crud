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
    public string $modalBtn = 'Enviar';

    protected $modelClass = User::class;
    public string $viewForm = 'livewire.modals.users-form';
    protected array $fillable = [
        'name',
        'email',
        'password'
    ];

    #[Validate('required|min:3')] 
    public $name;
    #[Validate('required|min:3')] 
    public $email;
    #[Validate('required|min:3')] 
    public $password;

    public function mount()
    {
        
    }

    #[Computed()]
    public function user(): User
    {
        return $this->model;
    }

    public function viewShow()
    {
        return view('livewire.modals.users-show');
    }

    public function viewDelete()
    {
        return view('livewire.modals.delete')
                    ->with('message', "Deseja realmente apagar o usuário {$this->user->name} ?");
    }

    public function created()
    {
        $this->dispatch('users-created');
    }

    public function updated()
    {
        $this->dispatch('users-updated');
    }

    public function destroyed()
    {
        $this->dispatch('users-destroyed');
    }

}
