<?php

namespace App\Helper;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\View\ViewableData;

/**
 * Usage:
 *
 * Call the Tags in the template.ss:
 *
 * These will figure out if your application is in Dev mode, depending on the isDev() method.
 * $Vite.HeaderTags.RAW
 * $Vite.BodyTags.RAW
 *
 * If $forceProductionMode is set to true, or a URL-param ?vprod is set,
 * production versions will be served.
 *
 * Or decide on your own:
 *
 * Dev-tags (available while vite dev-server is running):
 * $Vite.ClientScripts.RAW - Header
 * $Vite.DevScripts.RAW - after Body
 *
 * Production Tags (available after running vite build):
 * $Vite.CSS.RAW
 * $Vite.JS.RAW
 */
class ViteHelper extends ViewableData implements TemplateGlobalProvider
{
    use Configurable;

    /**
     * Disable dev scripts and serve the production files.
     */
    private bool $forceProductionMode;

    /**
     * Used in isDev() as a needle to test $_SERVER['HTTP_HOST'] if the site is running locally / in dev mode.
     * Set it to e.g. ".test", ".dev", "localhost" etc. to distinguish it from a live server environment.
     */
    private string $devHostNeedle;

    /**
     * Port where the ViteJS dev server will serve
     */
    private int $devPort;

    /**
     * Source directory for .js/.ts/.vue/.scss etc.
     */
    private string $jsSrcDirectory;

    /**
     * Main js / ts file.
     */
    private string $mainJS;

    /**
     * Relative path (from /public) to the manifest.json created by ViteJS after running the build command.
     */
    private string $manifestDir;

    public function __construct()
    {
        $config = self::config();

        $this->forceProductionMode = (bool)$config->forceProductionMode;
        $this->devHostNeedle = $config->devHostNeedle ?? '.test';
        $this->devPort = $config->devPort ?? 3000;
        $this->jsSrcDirectory = $config->jsSrcDirectory ?? 'public_src/';
        $this->mainJS = $config->mainJS ?? 'main.js';
        $this->manifestDir = $config->manifestDir ?? '/public/manifest.json';
    }

    /**
     * Serve script tags for insertion in the HTML head,
     * either for dev od production, depending on the isDev() method.
     */
    public function getHeaderTags(): string
    {
        return $this->isDev() ? $this->getDevScript() : $this->getCSS();
    }

    /**
     * Serve script tags for insertion at the end of HTML body,
     * either for dev od production, depending on the isDev() method.
     */
    public function getBodyTags(): string
    {
        return $this->isDev() ? $this->getClientScript() : $this->getJS();
    }

    /**
     * For production. Available after build.
     * Return the css files created by ViteJS
     */
    public function getCSS(): string
    {
        $manifest = $this->getManifest();

        if (!$manifest) {
            return '';
        }

        $style_tags = [];
        foreach ($manifest as $name => $item) {

            // If name includes css
            if (strpos($name, '.css') !== false) {
                $style_tags[] = $this->css('_resources/app/client/dist/'.$item->file);
            }
        }

        return implode("\n", $style_tags);
    }

    /**
     * For production. Available after build.
     * Return the most recent js file created by ViteJS
     *
     * Will return additional <script nomodule> tags
     * if @vite/plugin-legacy is installed.
     */
    public function getJS(): string
    {
        $manifest = $this->getManifest();
        if (!$manifest) {
            return '';
        }

        $script_tags = [];
        foreach ($manifest as $item) {

            if (!empty($item->isEntry)) {

                $params = [];
                if (strpos($item->src, 'legacy') !== false) {
                    $params[] = 'nomodule';
                } else {
                    $params['type'] = 'module';
                }

                $script_tags[] = $this->script('_resources/app/client/dist/'.$item->file, $params);
            }
        }

        /**
         * Legacy Polyfills must come first.
         */
        usort($script_tags, function ($tag) {
            return strpos($tag, 'polyfills') !== false ? 0 : 1;
        });

        /**
         * ES-module scripts must come last.
         */
        usort($script_tags, function ($tag) {
            return strpos($tag, 'type="module"') !== false ? 1 : 0;
        });

        return implode("\n", $script_tags);
    }

    /**
     * For dev mode at the end of HTML body.
     */
    public function getDevScript(): string
    {
        return $this->script('http://localhost:' . $this->devPort . '/@vite/client', [
            'type' => 'module',
        ]);
    }

    /**
     * For dev mode in HTML head.
     */
    public function getClientScript(): string
    {
        return $this->script('http://localhost:' . $this->devPort . '/' . $this->jsSrcDirectory . $this->mainJS, [
            'type' => 'module',
        ]);
    }

    /**
     * Get data on the files created by ViteJS
     * from /public/manifest.json
     */
    private function getManifest(): ?\stdClass
    {
//        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
//        $root = str_replace('public', '', $root);
//        $root = rtrim($root, '/');
//        if (!$root) {
//            return null;
//        }
        $root = BASE_PATH;

        $path = $root . $this->manifestDir;

        if (!file_exists($path)) {
            throw new \Exception('Could not find manifest.json at ' . $path);
        }

        $manifest = file_get_contents($path);
        if (PHP_VERSION_ID >= 80200 && function_exists('mb_convert_encoding')) {
            $manifest = mb_convert_encoding($manifest, 'UTF-8');
        } else {
            $manifest = utf8_encode($manifest);
        }

        if (!$manifest) {
            throw new \Exception('No ViteDataExtension manifest.json found. ');
        }

        $manifest = str_replace([
            "\u0000",
        ], '', $manifest);

        return json_decode($manifest);
    }

    /**
     * Decide what files to serve.
     *
     * If forceProductionMode is set to true or a ?vprod URL-param is set, it will always return false.
     */
    private function isDev(): bool
    {
        if ($this->forceProductionMode === true) {

            return false;
        }

        if (isset($_GET['vprod'])) {

            return false;
        }

        if (!empty($_COOKIE['vprod']) && $_COOKIE['vprod']) {

            return false;
        }

        return strpos($_SERVER['HTTP_HOST'] ?? '', $this->devHostNeedle) !== false;
    }

    private function css(string $url): string
    {
        return '<link rel="stylesheet" href="' . $url . '">';
    }

    private function script(string $url, array $params = []): string
    {
        $params_string = "";
        foreach ($params as $param => $value) {

            if (is_int($param)) {
                $params_string .= sprintf('%s ', $value);
                continue;
            }

            $params_string .= sprintf('%s="%s" ', $param, $value);
        }

        $script = '<script src="' . $url . '" ' . $params_string . '></script>';
        return $script;
    }

    public static function Vite(): self
    {
        return new self();
    }

    public static function get_template_global_variables(): array
    {
        return [
            'Vite',
        ];
    }
}