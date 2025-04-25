<?php

namespace ChrisHenrique\ModalCrud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChrisHenrique\ModalCrud\Modal
 */
class Modal extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'modal';
    }
}