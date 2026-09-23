<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * A modal that denies access to every record whose title is not 'allowed'.
 */
class AuthorizedModal extends ModalCrud
{
    protected $modelClass = Post::class;

    public string $title = '';

    public function rules(): array
    {
        return ['title' => 'required|string'];
    }

    protected function authorizeModal(): void
    {
        if ($this->model && $this->model->exists && $this->model->title !== 'allowed') {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
