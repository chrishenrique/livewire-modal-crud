/**
 * modal-crud core.
 *
 * Framework-agnostic wiring between Livewire and a modal implementation.
 * Adapters (bootstrap4.js, bootstrap5.js) call ModalCrud.register() with
 * show/hide/dispose primitives; everything else lives here.
 */
(function () {
    'use strict';

    if (window.ModalCrud) return;

    var ELEMENT_ID = 'modal-crud-base';

    var ModalCrud = {
        adapter: null,
        listenersBound: false,

        register: function (adapter) {
            this.adapter = adapter;
            this.bindGlobalListeners();
            this.whenReady(this.setup.bind(this));
        },

        /**
         * The shell is rendered by the Livewire component in <body>; deferred
         * scripts normally run after it exists, but an inlined or async load
         * may not.
         */
        whenReady: function (callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);

                return;
            }

            callback();
        },

        element: function () {
            return document.getElementById(ELEMENT_ID);
        },

        /**
         * Read the per-modal options the server rendered onto the element.
         */
        options: function (el) {
            return {
                closeOnClickAway: el.dataset.closeOnClickAway !== 'false',
                closeOnEscape: el.dataset.closeOnEscape !== 'false',
                destroyOnClose: el.dataset.destroyOnClose !== 'false',
                dispatchCloseEvent: el.dataset.dispatchCloseEvent !== 'false',
            };
        },

        /**
         * Bound once for the lifetime of the page. Binding these inside setup()
         * would stack a new listener every livewire:navigated, making a single
         * openModal call show the modal N times.
         */
        bindGlobalListeners: function () {
            if (this.listenersBound) return;
            this.listenersBound = true;

            var self = this;

            window.addEventListener('openModalInBrowser', function () {
                var el = self.element();
                if (!el || !self.adapter) return;

                self.adapter.show(el, self.options(el));
            });

            window.addEventListener('closeModalInBrowser', function () {
                var el = self.element();
                if (!el || !self.adapter) return;

                self.adapter.hide(el);
            });
        },

        /**
         * Attach the per-element hidden handler. Idempotent per element.
         */
        setup: function () {
            var el = this.element();
            if (!el || !this.adapter) return;
            if (el.dataset.modalCrudInitialized === 'true') return;

            el.dataset.modalCrudInitialized = 'true';

            var self = this;

            this.adapter.onHidden(el, function () {
                var options = self.options(el);

                if (options.destroyOnClose) {
                    window.Livewire.dispatch('destroyComponent');
                }

                if (options.dispatchCloseEvent) {
                    window.Livewire.dispatch('modalClosed');
                }
            });
        },

        /**
         * wire:navigate swaps the DOM and can leave the previous shell behind.
         * Drop the stale copies and re-bind against whatever survived.
         */
        onNavigated: function () {
            var shells = document.querySelectorAll('#' + ELEMENT_ID);

            for (var i = shells.length - 1; i > 0; i--) {
                if (this.adapter && this.adapter.dispose) {
                    this.adapter.dispose(shells[i]);
                }
                shells[i].remove();
            }

            this.setup();
        },

        warn: function (message) {
            if (window.console && console.warn) {
                console.warn('[modal-crud] ' + message);
            }
        },
    };

    window.ModalCrud = ModalCrud;

    document.addEventListener('livewire:navigated', function () {
        ModalCrud.onNavigated();
    });
})();
