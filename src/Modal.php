<?php

namespace ChrisHenrique\ModalCrud;

class Modal
{
    public function getJsPath(): string
    {
        $lib = config('modal-crud.lib');
        $exists = 'vendor/modal-crud/js/'.$lib.'.js';

        $path = public_path($exists);

        if (file_exists($path)) {
            return $exists;
        }

        return 'vendor/chrishenrique/modal-crud/resources/js/'.$lib.'.js';
    }
}