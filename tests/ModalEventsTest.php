<?php

namespace ChrisHenrique\ModalCrud\Tests;

use ChrisHenrique\ModalCrud\Tests\Fixtures\EventsModal;
use ChrisHenrique\ModalCrud\Tests\Fixtures\Listener;
use Livewire\Livewire;

class ModalEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Livewire::component('some-component', Listener::class);
    }

    /**
     * Regression: $params was declared inside the loop's if-branch and never
     * reset, so a bare event inherited the previous event's payload.
     */
    public function test_events_do_not_inherit_the_previous_payload(): void
    {
        Livewire::test(EventsModal::class, ['mode' => 'archive'])
            ->call('fireMixedEvents')
            ->assertDispatched('refresh-list', id: 7)
            ->assertDispatched('plain-event', function (string $event, array $params) {
                return $params === [];
            })
            ->assertDispatched('closeModalInBrowser');
    }

    public function test_named_keys_dispatch_to_a_specific_component(): void
    {
        Livewire::test(EventsModal::class, ['mode' => 'archive'])
            ->call('fireTargetedEvent')
            ->assertDispatchedTo('some-component', 'targeted', value: 'x')
            ->assertDispatched('closeModalInBrowser');
    }

    public function test_close_modal_dispatches_the_browser_event(): void
    {
        Livewire::test(EventsModal::class, ['mode' => 'archive'])
            ->call('closeModal')
            ->assertDispatched('closeModalInBrowser');
    }
}
