<?php

namespace ChrisHenrique\ModalCrud\Livewire\Contracts;

/**
 * Marks a Livewire component as openable through the `modal` component.
 *
 * `Modal::openModal()` calls each of these statically before mounting the
 * component, so they are part of the contract rather than conveniences
 * provided by ModalCrud. Implement this interface directly only if you also
 * implement every method below; extending ModalCrud gives you defaults.
 */
interface ModalContract
{
    /**
     * Extra class applied to `.modal-dialog` (e.g. `modal-lg`, `modal-fullscreen`).
     */
    public static function modalSize(): string;

    /**
     * Vertically center the dialog (`.modal-dialog-centered`).
     */
    public static function modalCentered(): bool;

    /**
     * Scroll the modal body instead of the page (`.modal-dialog-scrollable`).
     */
    public static function modalScrollable(): bool;

    /**
     * Allow clicking the backdrop to dismiss the modal.
     */
    public static function closeModalOnClickAway(): bool;

    /**
     * Allow the Escape key to dismiss the modal.
     */
    public static function closeModalOnEscape(): bool;

    /**
     * Tear the nested Livewire component down once the modal finishes hiding.
     */
    public static function destroyOnClose(): bool;

    /**
     * Dispatch a `modalClosed` Livewire event to the application on close.
     */
    public static function dispatchCloseEvent(): bool;
}
