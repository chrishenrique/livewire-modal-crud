<div>
     <div class="col-lg-8 mx-auto p-4 py-md-5">
        <main>
            <div class="mb-5">
                    <button class="btn btn-primary"
                        onclick="Livewire.dispatch('openModal', {
                            component: 'modals.users-form',
                            arguments: {
                                mode:'create',
                            }
                        })"
                    >Novo</button>
            </div>
            <div class="row g-5">
                <div class="col-12">
                    <ul class="h3">
                        @forelse ($this->users() as $user)
                            <li class="d-flex mb-1">
                                <button class="btn btn-info mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode:'show',
                                            id: {{ $user->id }},
                                        }
                                    })"
                                >Ver</button>
                                <button class="btn btn-primary mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode: 'edit',
                                            id: {{ $user->id }},
                                        }
                                    })"
                                >Editar</button>
                                <button class="btn btn-danger mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode:'delete',
                                            id: {{ $user->id }},
                                        }
                                    })"
                                >Apagar</button>
                                {{ $user->name }} 
                            </li>
                        @empty
                        <li>Empty user</li>
                        @endforelse
                    </ul>
                    
                </div>
            </div>
        </main>
    </div>
</div>
