<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Users extends Component
{
    #[On('users-created')]
    #[On('users-updated')]
    #[On('users-destroyed')]
    #[Computed()]
    public function users()
    {
        return \App\Models\User::all();
    }

    public function render()
    {
        return view('livewire.users');
    }
}
