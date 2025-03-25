@use('App\Models\User')

<x-app-layout>
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

            <hr class="col-3 col-md-2 mb-5">

            <div class="row g-5">
                <div class="col-12">
                    <ul class="h3">
                        @forelse (User::all() as $user)
                            <li class="d-flex">
                                {{ $user->name }} 
                                <button class="btn btn-primary"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode: 'edit',
                                            id: {{ $user->id }},
                                            model: '\\App\\Models\\User',
                                        }
                                    })"
                                >Editar</button>
                                <button class="btn btn-danger"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode:'delete',
                                            id: {{ $user->id }},
                                            model: '\\App\\Models\\User',
                                        }
                                    })"
                                >Apagar</button>
                            </li>
                        @empty
                        <li>Empty user</li>
                        @endforelse
                    </ul>
                    
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
