<?php

namespace ChrisHenrique\ModalCrud\Http\Controllers;

use ChrisHenrique\ModalCrud\ModalAssets;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssetController
{
    public function __invoke(ModalAssets $assets, string $script): Response
    {
        if (! in_array($script, ModalAssets::SCRIPTS, true)) {
            throw new NotFoundHttpException;
        }

        $path = $assets->path($script);

        if (! is_file($path)) {
            throw new NotFoundHttpException;
        }

        return new Response(file_get_contents($path), 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
