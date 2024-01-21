<?php
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use Atwx\ViteHelper\Helper\ViteHelper;

$editorConfig = HTMLEditorConfig::get('cms');
if(ViteHelper::getEditorCss()) {
    $editorCss = ViteHelper::getEditorCss();
    $contentCss = $editorConfig->getContentCSS();
    $contentCss[] = $editorCss;
    $editorConfig->setContentCSS($contentCss);
}
