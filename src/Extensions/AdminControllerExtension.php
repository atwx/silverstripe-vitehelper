<?php

namespace Atwx\ViteHelper\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use Atwx\ViteHelper\Helper\ViteHelper;
use SilverStripe\TinyMCE\TinyMCEConfig;

class AdminControllerExtension extends Extension
{
    public function onAfterInit(): void
    {
        /** @var TinyMCEConfig $editorConfig */
        $editorConfig = HTMLEditorConfig::get('cms');
        if (ViteHelper::getEditorCss()) {
            $editorCss = ViteHelper::getEditorCss();
            $contentCss = $editorConfig->getContentCSS();
            $contentCss[] = $editorCss;
            $editorConfig->setContentCSS($contentCss);
        }
    }
}
