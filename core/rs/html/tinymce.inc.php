<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Html;

/**
* HTML элемент. TinyMce
*/
class Tinymce implements ElementInterface
{
    protected 
        $options,
        $data;
    
    protected static $ids = array();
    
    
    function __construct($options, $data)
    {
        $this->options = array_replace_recursive(array(
            'tiny_options' => $this->getDefaultTinyOptions(),
            'template' => 'system/admin/html_elements/tinymce/textarea.tpl',
            'jquery_tinymce_path' => \Setup::$JS_PATH.'/tiny_mce/jquery.tinymce.min.js',
            'cols' => 100,
            'rows' => 20
        ), $options);
        
        $this->data = $data;
        self::$ids[] = $this->options['id'];
        
        $view = new \RS\View\Engine();
        $view->assign('param', $this->options);
        $view->assign('textarea_ids', implode(",", self::$ids));
    }
    
    /**
    * Возвращает HTML код визуального редактора
    * 
    * @return string
    */
    function getView()
    {
        $view = new \RS\View\Engine();
        $view->assign('param', $this->options);
        $view->assign('data', $this->data);
        return $view->fetch($this->options['template']);
    }
    
    /**
    * Возвращает настройки tinyMce по умолчанию
    * 
    * @return array
    */
    function getDefaultTinyOptions()
    {
        $tiny_options = array(
                'script_url'         => \Setup::$JS_PATH.'/tiny_mce/tiny_mce.min.js',
                'menubar'            => false,
                'toolbar_items_size' => 'small',
                'language'           => 'ru',
                'plugins'            => array("link image anchor", 
                                              "searchreplace wordcount visualblocks code fullscreen media", 
                                              "table contextmenu textcolor paste textcolor colorpicker responsivefilemanager lists"),
                'toolbar1'           => 'undo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect',
                'toolbar2'           => 'table | cut copy paste | searchreplace | bullist numlist | link unlink anchor responsivefilemanager image media code | forecolor backcolor | visualblocks fullscreen',
                'valid_elements'     => '*[*]',
                'valid_children'     => '+body[style]',
                'resize'             => 'both',

                'image_advtab'       => true ,
                'paste_data_images'  => true,
   
                'filemanager_title'         => t("Файловый менеджер"),
                'external_plugins'          => array( "filemanager" => \Setup::$JS_PATH.'/tiny_mce/filemanager/plugin.min.js' ),
                'filemanager_access_key'    => sha1(\Setup::$SECRET_KEY.\Setup::$SECRET_SALT),
                'external_filemanager_path' => \Setup::$JS_PATH.'/tiny_mce/filemanager/',
                
                'relative_urls'      => false,
                
                'cleanup_on_startup' => false,
                'trim_span_elements' => false,
                'verify_html'        => false,
                'cleanup'            => false,
                'remove_script_host' => true
        );
        
        $config = \RS\Config\Loader::getSiteConfig();
        $path_to_theme_css = \Setup::$FOLDER.
                             \Setup::$SM_RELATIVE_TEMPLATE_PATH.
                             '/'.$config->getThemeName().
                             \Setup::$RES_CSS_FOLDER.
                             '/layout.css';
                             
        if (file_exists(\Setup::$ROOT.$path_to_theme_css)) {
            $tiny_options['content_css'] = $path_to_theme_css;
        }
        
        return $tiny_options;
    }
}

