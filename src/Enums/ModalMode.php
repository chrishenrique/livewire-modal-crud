<?php

namespace ChrisHenrique\ModalCrud\Enums;

/**
 * The modes handled natively by ModalCrud.
 *
 * A modal's `$mode` is a free-form string on purpose — any value not listed
 * here falls through to `ModalCrud::action()` and to the default view. This
 * enum exists so the built-in modes can be referenced without magic strings.
 */
enum ModalMode: string
{
    case Show = 'show';
    case Create = 'create';
    case Edit = 'edit';
    case Delete = 'delete';

    /**
     * Resolve a raw mode string, returning null for custom application modes.
     */
    public static function parse(?string $mode): ?self
    {
        return $mode === null ? null : self::tryFrom($mode);
    }
}
