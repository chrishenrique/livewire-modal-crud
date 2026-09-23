/**
 * modal-crud adapter for Bootstrap 5. Requires core.js and Bootstrap's JS.
 */
(function () {
    'use strict';

    if (!window.ModalCrud) {
        console.error('[modal-crud] core.js must be loaded before bootstrap5.js.');
        return;
    }

    if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
        window.ModalCrud.warn("Bootstrap 5's JavaScript was not found on the page; modals will not open.");
        return;
    }

    /**
     * Bootstrap freezes backdrop/keyboard at construction time, so a modal
     * opened with different options needs a fresh instance.
     */
    function instance(el, options) {
        var signature = String(options.closeOnClickAway) + '|' + String(options.closeOnEscape);
        var existing = window.bootstrap.Modal.getInstance(el);

        if (existing && el.dataset.modalCrudSignature === signature) {
            return existing;
        }

        if (existing) existing.dispose();

        el.dataset.modalCrudSignature = signature;

        return new window.bootstrap.Modal(el, {
            backdrop: options.closeOnClickAway ? true : 'static',
            keyboard: options.closeOnEscape,
        });
    }

    window.ModalCrud.register({
        show: function (el, options) {
            instance(el, options).show();
        },
        hide: function (el) {
            var existing = window.bootstrap.Modal.getInstance(el);
            if (existing) existing.hide();
        },
        dispose: function (el) {
            var existing = window.bootstrap.Modal.getInstance(el);
            if (existing) existing.dispose();
        },
        onHidden: function (el, callback) {
            el.addEventListener('hidden.bs.modal', callback);
        },
    });
})();
