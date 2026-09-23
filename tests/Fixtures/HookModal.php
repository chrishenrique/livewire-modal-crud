<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * Records every lifecycle hook it receives so the tests can assert ordering.
 */
class HookModal extends ModalCrud
{
    protected $modelClass = Post::class;

    public string $title = '';

    /** @var array<int, string> */
    public array $calls = [];

    public function rules(): array
    {
        return ['title' => 'required|string|min:3'];
    }

    public function beforeValidate(): void
    {
        $this->calls[] = 'beforeValidate';
    }

    public function afterValidate(): void
    {
        $this->calls[] = 'afterValidate';
    }

    public function stored(): void
    {
        $this->calls[] = 'stored';
    }

    public function changed(): void
    {
        $this->calls[] = 'changed';
    }

    public function destroyed(): void
    {
        $this->calls[] = 'destroyed';
    }

    public function saved(): void
    {
        $this->calls[] = 'saved';
    }
}
