<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

class FakeModal extends ModalCrud
{
    public string $name = '';

    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    public static function modalSize(): string
    {
        return 'modal-lg';
    }
}
