<?php

namespace Atwx\ViteHelper\Helper;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\View\ViewableData;

class ViteHelper extends ViewableData implements TemplateGlobalProvider
{
    public function __construct()
    {
        parent::__construct();
        $this->devServerUrl = Environment::getEnv('VITE_DEV_SERVER_URL');
        $this->manifestPath = BASE_PATH . Environment::getEnv('VITE_MANIFEST_PATH');
        $this->outputUrl = RESOURCES_DIR . Environment::getEnv('VITE_OUTPUT_DIR');

        if (!$this->devServerUrl && !$this->manifestPath) {
            user_error('One of VITE_DEV_SERVER_URL or VITE_MANIFEST_PATH have to be set', E_USER_ERROR);
        }
    }

    public static function ViteClient(): string
    {
        $instance = singleton(self::class);
        if ($instance->devServerUrl) {
            return DBField::create_field('HTMLText', "<script type=\"module\" src=\"{$instance->devServerUrl}/@vite/client\"></script>");
        }
        return '';
    }

    public static function Vite($path): string
    {
        $instance = singleton(self::class);
        if ($instance->devServerUrl) {
            return Convert::raw2att("{$instance->devServerUrl}/{$path}");
        } else {
            if(!file_exists($instance->manifestPath))
                user_error('VITE_MANIFEST_PATH is set but the file does not exist. Did you build?', E_USER_ERROR);
            $manifest = json_decode(file_get_contents($instance->manifestPath), true);
            $path = $instance->outputUrl . $manifest[$path]['file'];
            return Convert::raw2att($path);
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
