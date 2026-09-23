# Changelog

All notable changes to `livewire-modal-crud` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-09-05

Livewire 4 support plus a round of correctness, security and packaging fixes.
See the [upgrade guide](#upgrading-from-1x) in the README before updating.

### Fixed

- **`store()`/`update()` persisted garbage when a modal declared no `$fillable`.**
  `Component::only()` reads its argument's *values* as property names, so the
  validated `attribute => value` map produced statements like
  `insert into posts ("Hello World")`. The validated data is now used directly,
  and `$fillable` is only consulted when it is set.
- **`vendor:publish --tag=modal-views` had no effect.** Views were registered
  under the `modalcrud` namespace but published to `views/vendor/modal-crud`,
  a directory Laravel never reads. The namespace is now `modal-crud` and
  matches the publish target. `modalcrud::` still resolves for 1.x
  compatibility and will be removed in 3.0.
- **`closeModalWithEvents()` leaked one event's payload into the next.**
  `$params` was not reset between loop iterations.
- **The JavaScript fallback path pointed at a file that never exists.**
  `public/vendor/chrishenrique/modal-crud/...` was a guaranteed 404 for anyone
  who had not run `vendor:publish --tag=modal-js`, leaving the modal inert with
  no server-side error. Scripts are now served by the package itself.
- **The `Modal` facade alias pointed at the concrete class**, so any static call
  failed with "non-static method cannot be called statically".
- **`resolveParameter()` sent every typed property through route-model binding.**
  A `Carbon`, DTO or value-object property failed with an unrelated
  `BadMethodCallException`. Only Eloquent models are bound now, and only
  *backed* enums go through `tryFrom()` (a pure enum used to fatal).
- **`openModal()` could throw on unserializable arguments.** Key generation used
  `serialize()`; it now uses `json_encode` and degrades to a random key.
- Bootstrap adapters registered a new `window` listener on every
  `livewire:navigated`, so one `openModal` call eventually showed the modal N
  times.

### Added

- `ModalCrud::authorizeModal()` — an authorization hook that runs on every
  request after the model is resolved. **Override it in every modal that reads
  or writes a record**; `mode` and `id` arrive from the browser.
- `#[Locked]` on `$mode` and `$id`, so the client cannot swap a `show` modal
  into a `delete` one between requests.
- `ChrisHenrique\ModalCrud\Enums\ModalMode` for the built-in modes. Custom mode
  strings keep working exactly as before.
- `ModalCrud::showSubmit()` — overridable, replacing the property that `boot()`
  used to overwrite unconditionally.
- `$deleteMessage` and `$showClose` properties; the delete confirmation text and
  the "Voltar" label were previously hardcoded in the view/component.
- The package now serves its own JavaScript from `/modal-crud/{script}.js`, so a
  fresh install works without `vendor:publish`. Publishing to
  `public/vendor/modal-crud/js` still takes precedence, and URLs are
  cache-busted by file mtime.
- `closeModalOnClickAway`, `closeModalOnEscape`, `destroyOnClose` and
  `dispatchCloseEvent` are now actually honoured by the frontend (see Changed).
- A `modalClosed` Livewire event, dispatched on close when
  `dispatchCloseEvent()` returns true.
- Accessibility attributes on the modal shell (`role`, `aria-modal`,
  `aria-labelledby`) and `wire:loading.attr="disabled"` on the submit button,
  which was previously double-clickable.
- GitHub Actions matrix (PHP 8.2–8.4 × Laravel 11–13, lowest and highest
  dependencies), PHPStan level 5 with Larastan, and Laravel Pint.
- 56 tests, up from 10, covering every fix above.

### Changed

- **Requires PHP 8.2+, Laravel 11+ and Livewire 4.** The 1.x constraint claimed
  Laravel 7–13 alongside Livewire 4, a combination that cannot resolve. The
  package now depends on the individual `illuminate/*` components rather than
  `laravel/framework`, and no longer sets `minimum-stability: dev`.
- Five static options (`closeModalOnClickAway()`, `closeModalOnEscape()`,
  `destroyOnClose()`, `dispatchCloseEvent()`, and formerly
  `closeModalOnEscapeIsForceful()`) were computed server-side and ignored by the
  JavaScript. They are now rendered as `data-*` attributes and read by the
  adapters.
- The JavaScript is split into a framework-agnostic `core.js` plus a
  `bootstrap4`/`bootstrap5` adapter. Both are loaded automatically. The
  adapters now warn on a missing jQuery or Bootstrap instead of failing
  silently.
- `openModal()` throws `InvalidArgumentException` rather than a bare
  `Exception` for an unresolvable or non-conforming component.
- `ModalContract` declares the static option methods it relies on, instead of
  being an empty marker interface.

### Removed

- **The stacked-modal API: `skipPreviousModal()`, `skipPreviousModals()`,
  `destroySkippedModals()`, `forceClose()`, and the `force`,
  `skipPreviousModals` and `destroySkipped` payloads on `closeModalInBrowser`.**
  The component stack backing them was commented out, so all of these were
  silent no-ops. Calls to them can simply be deleted.
- `closeModalOnEscapeIsForceful()`, which only had meaning with a modal stack.
- `Modal::resetState()`, which had an empty body.
- The deprecated `attributes` key on the component descriptor; use `arguments`.
- `ChrisHenrique\ModalCrud\View\Components\ModalComponent` and the
  `<x-modal-component>` Blade component, which rendered a view outside the
  package namespace and was used by nothing.
- The `modal-crud` Livewire component registration, which pointed at an
  abstract class and fatal-errored on use.
- `ChrisHenrique\ModalCrud\Modal` (the asset helper) is now
  `ChrisHenrique\ModalCrud\ModalAssets`, ending the three-way name collision
  with the facade and the Livewire component. `app('modal')` and the `Modal`
  facade are unchanged.

## [1.0.0] - 2025-04-25

- Initial release.
