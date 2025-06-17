<?php

namespace Atwx\ViteHelper\Helper;

use SilverStripe\Model\ModelData;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\Control\Director;

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

    /**
     * URL of the vitejs dev server
     *
     * @var string
     */
    private $devServerUrl;


    public function __construct()
    {
        parent::__construct();
        $this->devServerUrl = Environment::getEnv('VITE_DEV_SERVER_URL');
    }

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
        if ($instance->devServerUrl) {
            return DBField::create_field('HTMLText', "<script type=\"module\" src=\"{$instance->devServerUrl}/@vite/client\"></script>");
        }
        return '';
    }

    public static function Vite($path, int $getCSSFile = null): string
    {
        $instance = singleton(self::class);

        if ($instance->devServerUrl) {
            return Convert::raw2att("{$instance->devServerUrl}/{$path}");
        } else {
            $manifestPath = BASE_PATH . $instance->config()->get('manifest_path');
            if(!file_exists($manifestPath))
                user_error('manifest file does not exist. Did you build?', E_USER_ERROR);
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $outputUrl = RESOURCES_DIR . $instance->config()->get('output_url');

            if ($getCSSFile) {
                $path = $outputUrl . $manifest[$path]['css'][$getCSSFile - 1];
            } else {
                $path = $outputUrl . $manifest[$path]['file'];
            }
            return Convert::raw2att(Director::absoluteURL($path));
        }
    }

    public static function get_template_global_variables(): array
    {
        return [
            'Vite',
            'ViteClient',
        ];
    }

}
