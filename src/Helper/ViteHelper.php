<?php
namespace Atwx\ViteHelper\Helper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\TemplateGlobalProvider;

class ViteHelper extends ModelData implements TemplateGlobalProvider
{
    /**
     * Path to the vitejs manifest file. Will be prepended with BASE_PATH
     *
     * @config
     * @var string
     */
    private static $manifest_path = "/app/client/dist/.vite/manifest.json";

    /**
     * URL of the vitejs output directory. Will be prepended with RESOURCES_DIR
     *
     * @config
     * @var string
     */
    private static $output_url = "/app/client/dist/";

    /**
     * CSS to be added to the editor. This has to be the key in the manifest.json
     *
     * @config
     * @var string
     */
    private static $editor_css = null;

    private static ?bool $devServerIsRunning = null;

    public static function getEditorCss(): string|null
    {
        $instance = singleton(self::class);
        if ($instance->config()->get('editor_css')) {
            $editorCss = $instance->config()->get('editor_css');
            return self::Vite($editorCss);
        }
        return null;
    }

    public static function ViteClient(): string
    {
        $instance = singleton(self::class);
        if (self::devServerIsRunning()) {
            return DBField::create_field('HTMLText',
                '<script type="module" src="' . self::getDevServerURL() . '/@vite/client"></script>');
        }
        return '';
    }

    public static function Vite($path, ?int $getCSSFile = null): string
    {
        if (self::devServerIsRunning()) {
            return Convert::raw2att(self::getDevServerURL() . "/{$path}");
        }
        $manifestPath = BASE_PATH . self::config()->get('manifest_path');
        if (!file_exists($manifestPath)) {
            user_error('manifest file does not exist. Did you build?', E_USER_ERROR);
        }
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $outputUrl = RESOURCES_DIR . self::config()->get('output_url');

        if ($getCSSFile) {
            $path = $outputUrl . $manifest[$path]['css'][$getCSSFile - 1];
        } else {
            $path = $outputUrl . $manifest[$path]['file'];
        }
        $absolutePath = Director::absoluteURL($path);
        return Convert::raw2att($absolutePath);
    }

    public static function get_template_global_variables(): array
    {
        return [
            'Vite',
            'ViteClient',
        ];
    }

    private static function getDevServerURL()
    {
        return Environment::getEnv('VITE_DEV_SERVER_URL');
    }

    private static function devServerIsRunning(): bool
    {
        if (!Director::isDev()) {
            return false;
        }

        $devServerUrl = self::getDevServerURL();
        if (!$devServerUrl) {
            return false;
        }

        if (self::$devServerIsRunning !== null) {
            return self::$devServerIsRunning;
        }

        //$devServerURL is e.g. https://project.ddev.site:5173/
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 0.5 // 500ms Timeout
            ]
        ]);

        $headers = @get_headers($devServerUrl, true, $ctx);
        if ($headers === false) {
            return false;
        }

        //If vite dev server is not running, we might get a 502 Bad Gateway from ddev
        $statusLine = $headers[0] ?? '';
        self::$devServerIsRunning =  !str_contains($statusLine, '502');
        return self::$devServerIsRunning;

    }

}
