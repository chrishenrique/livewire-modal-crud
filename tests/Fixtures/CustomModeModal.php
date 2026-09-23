<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * Exercises a mode outside the built-in enum, which must fall through to
 * action() and to the default view.
 */
class CustomModeModal extends ModalCrud
{
    public bool $actioned = false;

    public string $modalTitle = 'Arquivar';

    protected function action(): void
    {
        $this->actioned = true;

        parent::action();
    }
}
