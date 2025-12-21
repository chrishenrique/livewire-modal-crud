@assets
<script src="{{ asset($libPath) }}" defer></script>
@endassets

<div>
    <div id="modal-crud-base" class="modal fade" tabindex="-1">
        <div 
            @class([
                'modal-dialog',
                Arr::get($component ?? [], 'modalAttributes.size'),
                'modal-dialog-centered' => (bool)Arr::get($component ?? [], 'modalAttributes.centered'),
                'modal-dialog-scrollable' => (bool)Arr::get($component ?? [], 'modalAttributes.scrollable'),
            ]) role="document">
            @isset($component)
            @livewire($component['name'], $component['arguments'], key($component['id']))
            @endisset
        </div>
    </div>
</div>
