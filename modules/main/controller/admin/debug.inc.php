<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Admin;

class Debug extends \RS\Controller\Admin\Front
{
    function actionShowVars()
    {
        $this->wrapOutput(false);
        $toolgroup = $this->url->get('toolgroup', TYPE_STRING, 0);

        $group = \RS\Debug\Group::getInstance($toolgroup);
        $vars = $group->getData('info', 'vars', array());
        $this->view->assign('var_list', $vars);
        $this->app->removeJs()->removeCss();
        $this->app->title->addSection(t('Список переменных в шаблоне'));
        
        return $this->wrapHtml( $this->view->fetch('%system%/debug/showvars.tpl') );
    }
}

