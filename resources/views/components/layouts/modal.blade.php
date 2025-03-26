<div class="modal-content">
    <div class="modal-header">
        <h1 class="modal-title fs-5">{{ $modalTitle }}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    @yield('content')
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $modalClose }}</button>
        
        @if($showSubmit)
        <button type="button" class="btn btn-primary" wire:click='submit'>{{ $modalBtn }}</button>
        @endif
    </div>
</div>
