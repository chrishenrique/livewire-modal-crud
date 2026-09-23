<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * Persists through an explicit $fillable allowlist.
 */
class PostModal extends ModalCrud
{
    protected $modelClass = Post::class;

    public array $fillable = ['title'];

    public string $title = '';

    public string $ignored = 'not persisted';

    public function rules(): array
    {
        return ['title' => 'required|string|min:3'];
    }
}
