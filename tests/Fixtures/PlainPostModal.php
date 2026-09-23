<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * No $fillable — persists exactly the validated data. Guards the bug where
 * Component::only() was handed an `attribute => value` map and used the values
 * as column names.
 */
class PlainPostModal extends ModalCrud
{
    protected $modelClass = Post::class;

    public string $title = '';

    public ?string $slug = null;

    public string $untouched = 'never validated';

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'slug' => 'nullable|string',
        ];
    }
}
