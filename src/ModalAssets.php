<?php

namespace ChrisHenrique\ModalCrud;

use InvalidArgumentException;

/**
 * Locates and versions the package's JavaScript.
 *
 * The scripts are served by the package's own route by default, so a fresh
 * install works without `vendor:publish`. Publishing to
 * `public/vendor/modal-crud/js` still wins, which is how you customise them.
 */
class ModalAssets
{
    /**
     * Scripts that may be requested through the asset route.
     *
     * Acts as an allowlist: the route parameter is never used to build a path
     * directly, so `../` traversal cannot reach outside this set.
     *
     * @var array<int, string>
     */
    public const SCRIPTS = ['core', 'bootstrap4', 'bootstrap5'];

    /**
     * The frontend library configured in `config/modal-crud.php`.
     */
    public function lib(): string
    {
        $lib = (string) config('modal-crud.lib', 'bootstrap5');

        if (! in_array($lib, self::SCRIPTS, true) || $lib === 'core') {
            throw new InvalidArgumentException(
                "Unsupported [modal-crud.lib] value [{$lib}]. Supported: bootstrap4, bootstrap5."
            );
        }

        return $lib;
    }

    /**
     * URLs to load, in order: the framework-agnostic core, then the adapter.
     *
     * @return array<int, string>
     */
    public function scripts(): array
    {
        return [
            $this->url('core'),
            $this->url($this->lib()),
        ];
    }

    /**
     * Public URL for one script, cache-busted by the file's mtime.
     */
    public function url(string $script): string
    {
        $this->guard($script);

        $published = $this->publishedPath($script);

        if ($published !== null) {
            return asset('vendor/modal-crud/js/'.$script.'.js').'?id='.$this->version($script);
        }

        return route('modal-crud.asset', ['script' => $script, 'id' => $this->version($script)]);
    }

    /**
     * Absolute path of the file to serve: the published copy if present,
     * otherwise the one shipped with the package.
     */
    public function path(string $script): string
    {
        $this->guard($script);

        return $this->publishedPath($script) ?? __DIR__.'/../resources/js/'.$script.'.js';
    }

    /**
     * Short hash of the file's mtime, used for cache busting.
     */
    public function version(string $script): string
    {
        $mtime = @filemtime($this->path($script));

        return substr(md5((string) ($mtime ?: 'dev')), 0, 8);
    }

    protected function publishedPath(string $script): ?string
    {
        $path = public_path('vendor/modal-crud/js/'.$script.'.js');

        return is_file($path) ? $path : null;
    }

    protected function guard(string $script): void
    {
        if (! in_array($script, self::SCRIPTS, true)) {
            throw new InvalidArgumentException("Unknown modal-crud script [{$script}].");
        }
    }
}
