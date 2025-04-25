# Livewire Modal CRUD

This package provides a simple structure to implement CRUD operations (Create, Read, Update, Delete) using modals with [Livewire](https://laravel-livewire.com/) in Laravel. It's ideal for applications that need a dynamic and interactive interface without page reloads.

## ✨ Features

- Ready-to-use Livewire components with modal support  
- Tailwind CSS integration for responsive styling  
- Code organization following Laravel best practices  
- Easy customization and extensibility  

## 🚀 Installation

To install the package via Composer, run:

```bash
composer require chrishenrique/livewire-modal-crud
```

After installation, publish the files with the commands below:

```bash
php artisan vendor:publish --tag=modal-config
php artisan vendor:publish --tag=modal-js
php artisan vendor:publish --tag=modal-views
```

## ⚙️ Setup

Make sure Livewire is installed in your project. If not, install it with:

```bash
composer require livewire/livewire
```

Include Livewire styles and scripts in your Blade layout:

```blade
<head>
    ...
    @livewireStyles
</head>
<body>
    ...
    @livewireScripts
</body>
```

## 🧩 Usage

Include the `modal` component once in your layout (usually in your main Blade view):

```blade
@livewire('modal')
```

### Opening a modal

To open a modal dynamically, use:

```blade
<button class="btn btn-primary"
    onclick="Livewire.dispatchTo('modal', 'openModal', {
        component: 'modals.users-form',
        arguments: {
            mode: 'create',
        }
    })"
> New </button>
```

### Modes

The default accepted values for the `mode` argument are:

- `'create'`
- `'edit'`
- `'delete'`
- `'show'`

However, you can use any custom `string` value according to your app logic.

### Passing `id` for model binding

To open a modal with a specific model instance, just include the `id` parameter:

```blade
<button class="btn btn-primary mx-1"
    onclick="Livewire.dispatchTo('modal', 'openModal', {
        component: 'modals.users-form',
        arguments: {
            mode: 'edit',
            id: {{ $user->id }},
        }
    })"
> Edit </button>
```

## 📝 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

---

For help, suggestions, or contributions, visit the GitHub repository: [chrishenrique/livewire-modal-crud](https://github.com/chrishenrique/livewire-modal-crud)