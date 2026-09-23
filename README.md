# Livewire Modal CRUD

Build CRUD modals with Livewire without wiring the plumbing yourself: dispatch
one browser event, and the package mounts your Livewire component inside a
Bootstrap modal, resolves the record, picks the title and the button labels from
the mode, and closes the modal when you are done.

[![tests](https://github.com/chrishenrique16/livewire-modal-crud/actions/workflows/tests.yml/badge.svg)](https://github.com/chrishenrique16/livewire-modal-crud/actions/workflows/tests.yml)

## Requirements

| Package | Version |
| --- | --- |
| PHP | 8.2+ |
| Laravel | 11, 12, 13 |
| Livewire | 4.x |
| Bootstrap | 4 (with jQuery) or 5 — **loaded by your application** |

The package renders Bootstrap markup and drives Bootstrap's modal JavaScript. It
does not bundle Bootstrap; your layout must already load it.

| Package version | Livewire | Laravel |
| --- | --- | --- |
| 2.x | 4.x | 11–13 |
| 1.x | 3.x | 10–12 |

## Installation

```bash
composer require chrishenrique/livewire-modal-crud
```

That is all that is required — the package serves its own JavaScript. Publish
only what you want to customise:

```bash
php artisan vendor:publish --tag=modal-config   # config/modal-crud.php
php artisan vendor:publish --tag=modal-views    # resources/views/vendor/modal-crud
php artisan vendor:publish --tag=modal-js       # public/vendor/modal-crud/js
```

Published JavaScript takes precedence over the built-in files.

### Layout

Livewire's own assets plus the modal host, once:

```blade
<head>
    @livewireStyles
</head>
<body>
    {{-- ... --}}

    @livewire('modal')

    @livewireScripts
</body>
```

### Configuration

```php
// config/modal-crud.php
return [
    // bootstrap4 (requires jQuery) or bootstrap5
    'lib' => 'bootstrap5',

    // URI the package serves its JavaScript from when not published
    'asset_route_prefix' => 'modal-crud',
];
```

## Writing a modal

A modal is a Livewire component extending `ModalCrud`. Point `$modelClass` at
the record it manages, declare `rules()`, and give it a form view.

```php
namespace App\Livewire\Modals;

use App\Models\User;
use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

class UsersForm extends ModalCrud
{
    protected $modelClass = User::class;

    public string $viewForm = 'livewire.modals.users-form';

    public string $name = '';
    public ?string $email = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$this->id,
        ];
    }

    protected function authorizeModal(): void
    {
        $this->authorize($this->getMode(), $this->model);
    }
}
```

The view extends the package layout and fills the `content` section. Everything
around it — header, title, footer, submit and cancel buttons — is supplied for
you:

```blade
{{-- resources/views/livewire/modals/users-form.blade.php --}}
@extends('modal-crud::components.layouts.modal')

@section('content')
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control" wire:model="name">
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" wire:model="email">
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>
@endsection
```

### Opening it

```blade
<button class="btn btn-primary"
    onclick="Livewire.dispatchTo('modal', 'openModal', {
        component: 'modals.users-form',
        arguments: { mode: 'create' }
    })"
>Novo</button>

<button class="btn btn-secondary"
    onclick="Livewire.dispatchTo('modal', 'openModal', {
        component: 'modals.users-form',
        arguments: { mode: 'edit', id: {{ $user->id }} }
    })"
>Editar</button>
```

`openModal` takes a third argument to override the modal's appearance for a
single opening:

```js
Livewire.dispatchTo('modal', 'openModal', {
    component: 'modals.users-form',
    arguments: { mode: 'edit', id: 1 },
    modalAttributes: { size: 'modal-xl', closeOnClickAway: false }
})
```

## Security

> [!IMPORTANT]
> `component`, `mode` and `id` all come from the browser. Anyone who can guess a
> component name and a record id can dispatch `openModal` for it.

The `ModalContract` check that `openModal()` performs is routing, not
authorization — every `ModalCrud` satisfies it by inheritance. **Each modal is
responsible for its own access control.** Override `authorizeModal()`:

```php
protected function authorizeModal(): void
{
    // Runs on every request, after the model is resolved.
    $this->authorize($this->getMode(), $this->model);
}
```

It runs on the initial mount *and* on every subsequent round-trip, so access
revoked mid-session takes effect immediately. `$mode` and `$id` are `#[Locked]`,
so the client cannot flip a `show` modal into a `delete` one.

Modals with no `$modelClass` and no sensitive data can leave the hook alone.

## Modes

| Mode | Title | `submit()` calls | View |
| --- | --- | --- | --- |
| `create` | `$createTitle` | `store()` | `viewCreate()` |
| `edit` | `$editTitle` | `update()` | `viewEdit()` |
| `delete` | `$deleteTitle` | `destroy()` | `viewDelete()` |
| `show` | `$showTitle` | — (no submit button) | `viewShow()` |
| *anything else* | `$modalTitle` | `action()` | `viewDefault()` |

Any other string is a valid mode; it falls through to `action()`, which you
override for custom workflows:

```php
class ArchiveModal extends ModalCrud
{
    protected function action(): void
    {
        $this->model->archive();

        parent::action(); // closes the modal and fires the hooks
    }
}
```

The built-in modes are also available as
`ChrisHenrique\ModalCrud\Enums\ModalMode` if you prefer not to use strings.

## Lifecycle hooks

Define any of these on your modal; they are called if they exist.

| Hook | When |
| --- | --- |
| `authorizeModal()` | Every request, right after the model is resolved |
| `beforeValidate()` | Before `validate()`, on create and edit |
| `afterValidate()` | After `validate()` succeeds, before persisting |
| `stored()` | After a successful `create` |
| `changed()` | After a successful `edit` |
| `destroyed()` | After a successful `delete` |
| `saved()` | After any of the three above, and after `action()` |

```php
protected function afterValidate(): void
{
    $this->email = strtolower($this->email);
}

public function saved(): void
{
    $this->dispatch('users-updated')->to('users-table');
}
```

## Closing with events

To refresh a listing when the modal closes:

```php
public function saved(): void
{
    $this->closeModalWithEvents([
        'users-table' => ['refresh', ['page' => 1]],  // to a specific component
        ['user-saved', ['id' => $this->model->id]],   // global, with payload
        'something-happened',                          // global, no payload
    ]);
}
```

## Per-modal options

Override any of these statics on your modal class.

| Method | Default | Effect |
| --- | --- | --- |
| `modalSize()` | `''` | Class on `.modal-dialog` (`modal-sm`, `modal-lg`, `modal-xl`, `modal-fullscreen`, …) |
| `modalCentered()` | `true` | Vertically centers the dialog |
| `modalScrollable()` | `true` | Scrolls the body instead of the page |
| `closeModalOnClickAway()` | `true` | Backdrop click dismisses |
| `closeModalOnEscape()` | `true` | Escape dismisses |
| `destroyOnClose()` | `true` | Tears the component down after hiding |
| `dispatchCloseEvent()` | `true` | Dispatches a `modalClosed` Livewire event on close |

```php
public static function modalSize(): string
{
    return 'modal-xl';
}
```

## Customising labels

Every label is a public property you can set on the class or pass through
`arguments`:

```php
public string $createTitle = 'Cadastrar usuário';
public string $editTitle = 'Editar usuário';
public string $deleteTitle = 'Remover usuário';
public string $deleteMessage = 'Esta ação não pode ser desfeita. Continuar?';
public string $modalBtn = 'Salvar';
public string $deleteBtn = 'Remover';
public string $modalClose = 'Cancelar';
public string $showClose = 'Voltar';
```

To restrict what gets persisted, set `$fillable`. Leave it empty and the
validated data is saved as-is:

```php
public array $fillable = ['name', 'email'];
```

## Upgrading from 1.x

2.0 requires **Livewire 4, Laravel 11+ and PHP 8.2+**.

1. **Stacked-modal calls must be removed.** `skipPreviousModal()`,
   `skipPreviousModals()`, `destroySkippedModals()` and `forceClose()` were
   no-ops backed by a commented-out component stack. Delete the calls.
2. **Re-publish your views.** Views now live under the `modal-crud` namespace
   and publish to `resources/views/vendor/modal-crud`. The old `modalcrud::`
   prefix still resolves but is deprecated. In 1.x the publish tag wrote to a
   directory Laravel never read, so your published copies were being ignored —
   check them against the new defaults before re-publishing.
3. **`vendor:publish --tag=modal-js` is optional now.** Delete
   `public/vendor/modal-crud/js` unless you customised it; the package serves
   its own scripts.
4. **`ChrisHenrique\ModalCrud\Modal` is now `ModalAssets`.** The `Modal` facade
   and `app('modal')` are unchanged. If you were type-hinting the concrete
   class, rename it.
5. **`<x-modal-component>` was removed.** It rendered a view outside the package
   and was used by nothing.
6. **`$this->component['attributes']` was removed** from the descriptor; use
   `arguments`.
7. **Add `authorizeModal()` to every modal that touches a record.** 1.x had no
   authorization hook at all, so this is likely a gap in your application, not
   just an upgrade step.

See the [CHANGELOG](CHANGELOG.md) for the full list.

## Testing

```bash
composer test        # phpunit
composer analyse     # phpstan
composer format      # pint
```

## License

MIT. See [LICENSE](LICENSE).

Issues and pull requests: [chrishenrique/livewire-modal-crud](https://github.com/chrishenrique16/livewire-modal-crud).
