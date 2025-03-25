<div>
    <div 
        id="modal-component"
        class="modal fade show"
        data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true"
        >
        @if(isset($component))
        <div class="modal-dialog modal-dialog-centered {{ Arr::get($component, 'attributes.modal_size') }}" role="document">
            @livewire($component['name'], $component['arguments'], key($component['id']))
        </div>
        @endif
    </div>
</div>
