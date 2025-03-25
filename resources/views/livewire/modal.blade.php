<div>
    <div 
        id="modal-component"
        class="modal fade show"
        >
        <div class="modal-dialog modal-dialog-centered {{ Arr::get($component ?? [], 'modalAttributes.size') }}" role="document">
            @isset($component)
            @livewire($component['name'], $component['arguments'], key($component['id']))
            @endisset
        </div>
    </div>
</div>
