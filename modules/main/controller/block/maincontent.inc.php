<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Block;

/**
* Блок - главное содержимое
* @ingroup Main
*/
class MainContent extends \RS\Controller\Block
{
    protected static
        $controller_title = 'Главное содержимое страницы',
        $controller_description = 'Возвращает содержимое главного модуля, который обрабатывает страницу';
    
    function actionIndex()
    {
        return \RS\Application\Application::getInstance()->blocks->getMainContent();
    }
}

