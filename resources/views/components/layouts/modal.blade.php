<div class="modal-content">
    <div class="modal-header">
        <h2 id="modal-crud-title" class="modal-title fs-5">{{ $modalTitle }}</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
    </div>

    @yield('content')

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">
            {{ $modalClose }}
        </button>

        @if ($showSubmit)
            <button
                type="button"
                class="btn btn-primary"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
            >{{ $modalBtn }}</button>
        @endif
    </div>
</div>
