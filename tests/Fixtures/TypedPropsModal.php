<?php

namespace ChrisHenrique\ModalCrud\Tests\Fixtures;

use Carbon\CarbonImmutable;
use ChrisHenrique\ModalCrud\Enums\ModalMode;
use ChrisHenrique\ModalCrud\Livewire\ModalCrud;

/**
 * Covers the three branches of Modal::resolveParameter(): an Eloquent model
 * (route-model bound), a backed enum, and a typed class that is neither and
 * must therefore be left untouched.
 */
class TypedPropsModal extends ModalCrud
{
    public ?Post $post = null;

    public ?ModalMode $resolvedMode = null;

    public ?CarbonImmutable $date = null;

    public function rules(): array
    {
        return [];
    }
}
