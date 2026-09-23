<?php

namespace ChrisHenrique\ModalCrud\Facades;

use ChrisHenrique\ModalCrud\ModalAssets;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string lib()
 * @method static array<int, string> scripts()
 * @method static string url(string $script)
 * @method static string path(string $script)
 * @method static string version(string $script)
 *
 * @see ModalAssets
 */
class Modal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'modal';
    }
}
