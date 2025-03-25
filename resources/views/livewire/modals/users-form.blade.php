@extends('components.layouts.modal')
@section('content')
<div class="modal-body">
    <div class="form-group">
        <label for="name">Nome</label>
        <input type="text" class="form-control" name="name" id="name" wire:model="name">
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="text" class="form-control" name="email" id="email" wire:model="email">
    </div>
    <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" class="form-control" name="password" id="password" wire:model="password">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $modalClose = 'Cancelar' }}</button>
    <button type="button" class="btn btn-primary" wire:click='submit'>{{ $modalBtn = 'Enviar' }}</button>
</div>
@endsection