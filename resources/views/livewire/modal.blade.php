@assets
    @foreach ($assets->scripts() as $script)
        <script src="{{ $script }}" defer></script>
    @endforeach
@endassets

@php
    $modalAttributes = $component['modalAttributes'] ?? [];
@endphp

<div>
    <div
        id="modal-crud-base"
        class="modal fade"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-crud-title"
        data-close-on-click-away="{{ ($modalAttributes['closeOnClickAway'] ?? true) ? 'true' : 'false' }}"
        data-close-on-escape="{{ ($modalAttributes['closeOnEscape'] ?? true) ? 'true' : 'false' }}"
        data-destroy-on-close="{{ ($modalAttributes['destroyOnClose'] ?? true) ? 'true' : 'false' }}"
        data-dispatch-close-event="{{ ($modalAttributes['dispatchCloseEvent'] ?? true) ? 'true' : 'false' }}"
    >
        <div @class([
            'modal-dialog',
            $modalAttributes['size'] ?? '',
            'modal-dialog-centered' => (bool) ($modalAttributes['centered'] ?? false),
            'modal-dialog-scrollable' => (bool) ($modalAttributes['scrollable'] ?? false),
        ])>
            @isset($component)
                @livewire($component['name'], $component['arguments'], key($component['id']))
            @endisset
        </div>
    </div>
</div>
