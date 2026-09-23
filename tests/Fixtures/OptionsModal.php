<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * Overrides every static option to the non-default value, so a value silently
 * dropped between openModal() and the rendered markup is visible.
 */
class OptionsModal extends ModalCrud
{
    public static function modalSize(): string
    {
        return 'modal-xl';
    }

    public static function modalCentered(): bool
    {
        return false;
    }

    public static function modalScrollable(): bool
    {
        return false;
    }

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }

    public static function closeModalOnEscape(): bool
    {
        return false;
    }

    public static function destroyOnClose(): bool
    {
        return false;
    }

    public static function dispatchCloseEvent(): bool
    {
        return false;
    }
}
