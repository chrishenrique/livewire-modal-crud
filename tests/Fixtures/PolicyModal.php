<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * The exact authorization pattern the README documents.
 */
class PolicyModal extends ModalCrud
{
    protected $modelClass = Post::class;

    public string $title = '';

    public function rules(): array
    {
        return ['title' => 'required|string'];
    }

    protected function authorizeModal(): void
    {
        $this->authorize($this->getMode(), $this->model);
    }
}
