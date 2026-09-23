<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

class EventsModal extends ModalCrud
{
    /**
     * A payload-carrying event followed by a bare one: if $params leaks across
     * iterations the second event is dispatched with the first one's payload.
     */
    public function fireMixedEvents(): void
    {
        $this->closeModalWithEvents([
            ['refresh-list', ['id' => 7]],
            'plain-event',
        ]);
    }

    public function fireTargetedEvent(): void
    {
        $this->closeModalWithEvents([
            'some-component' => ['targeted', ['value' => 'x']],
        ]);
    }
}
