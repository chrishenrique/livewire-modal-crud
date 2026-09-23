<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use Livewire\Component;

/**
 * A dispatch target for closeModalWithEvents(); Livewire resolves the
 * component name when an event is addressed with ->to().
 */
class Listener extends Component
{
    public function render()
    {
        return '<div></div>';
    }
}
