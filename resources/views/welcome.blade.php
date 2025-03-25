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
                                model: '\\App\\Models\\User',
                            }
                        })"
                    >Novo</button>
            </div>
            <div class="row g-5">
                <div class="col-12">
                    <ul class="h3">
                        @forelse (User::all() as $user)
                            <li class="d-flex mb-1">
                                <button class="btn btn-info mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode:'show',
                                            id: {{ $user->id }},
                                            model: '\\App\\Models\\User',
                                        }
                                    })"
                                >Ver</button>
                                <button class="btn btn-primary mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode: 'edit',
                                            id: {{ $user->id }},
                                            model: '\\App\\Models\\User',
                                        }
                                    })"
                                >Editar</button>
                                <button class="btn btn-danger mx-1"
                                    onclick="Livewire.dispatch('openModal', {
                                        component: 'modals.users-form',
                                        arguments: {
                                            mode:'delete',
                                            id: {{ $user->id }},
                                            model: '\\App\\Models\\User',
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
</x-app-layout>
