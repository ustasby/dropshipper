<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Templates\Controller\Admin;

use Templates\Model\MarketplaceThemeApi;

class SelectTheme extends \RS\Controller\Admin\Front
{
    protected
        $helper,
        $api;
    
    function init()
    {
        $this->api = new \RS\Theme\Manager();
    }
    
    function actionIndex()
    {
        $this->view->assign('list_html', $this->actionAjaxThemeList(true));
        return $this->result->setTemplate( 'select_theme.tpl' );
    }
    
    function actionAjaxThemeList($as_string = false)
    {
        $theme_list = $this->api->getList();
        $current_theme = \RS\Theme\Manager::getCurrentTheme();
        
        $this->view->assign(array(
            'theme_list' => $theme_list,
            'current' => $current_theme,
        ));
        $html = $this->view->fetch('select_theme_list.tpl');
        if ($as_string) {
            return $html;
        } else {
            return $this->result->setHtml($html);
        }
    }
    
    function actionUploadTheme()
    {
        // Если установлен запрет на установку тем
        if(defined('CANT_UPLOAD_THEME')) return;

        $overwrite = $this->url->request('overwrite', TYPE_INTEGER);
        $file = $this->url->files('theme', TYPE_ARRAY, array());
        return $this->result
            ->setSuccess( $this->api->uploadThemeZip($file, $overwrite) )
            ->setErrors( $this->api->getDisplayErrors() );
    }
    
    function actionInstallTheme()
    {
        $theme = $this->url->request('theme', TYPE_STRING);
        $result = $this->api->setTheme($theme);
        $this->result->setSuccess( $result === true );
        if (!$result) {
            $this->result->setErrors($this->api->getDisplayErrors());
        } 
        
        return $this->result;
    }


    function actionLoadMarketplace()
    {
        $marketplace_theme_api = new MarketplaceThemeApi();

        $this->view->assign(array(
            'theme_mp_list' =>  $marketplace_theme_api->getMarketplaceThemes()
        ));

        return $this->result->setTemplate('select_theme_mp_list.tpl');
    }

}