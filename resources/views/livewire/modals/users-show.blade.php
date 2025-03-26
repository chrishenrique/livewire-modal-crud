@extends('components.layouts.modal')
@section('content')
<div class="modal-body">
    <div class="form-group">
        <label for="name">Nome</label>
        <input type="text" class="form-control" name="name" id="name" wire:model="name"  @disabled(true)>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="text" class="form-control" name="email" id="email" wire:model="email"  @disabled(true)>
         @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>
@endsection