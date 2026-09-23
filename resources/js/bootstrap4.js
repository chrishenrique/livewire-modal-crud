/**
 * modal-crud adapter for Bootstrap 4. Requires core.js, jQuery and Bootstrap's JS.
 */
(function () {
    'use strict';

    if (!window.ModalCrud) {
        console.error('[modal-crud] core.js must be loaded before bootstrap4.js.');
        return;
    }

    if (typeof window.jQuery === 'undefined') {
        window.ModalCrud.warn('Bootstrap 4 requires jQuery, which was not found on the page.');
        return;
    }

    var $ = window.jQuery;

    if (typeof $.fn.modal === 'undefined') {
        window.ModalCrud.warn("Bootstrap 4's JavaScript was not found on the page; modals will not open.");
        return;
    }

    window.ModalCrud.register({
        show: function (el, options) {
            $(el).modal({
                backdrop: options.closeOnClickAway ? true : 'static',
                keyboard: options.closeOnEscape,
                show: true,
            });
        },
        hide: function (el) {
            $(el).modal('hide');
        },
        dispose: function (el) {
            $(el).modal('dispose');
        },
        onHidden: function (el, callback) {
            $(el).on('hidden.bs.modal', callback);
        },
    });
})();
