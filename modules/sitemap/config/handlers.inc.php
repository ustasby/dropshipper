<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Sitemap\Config;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this->bind('getroute');
    }
    
    public static function getRoute($routes) 
    {
        $routes[] = new \RS\Router\Route('sitemap-front-sitemap', array(
            '/sitemap-{site_id}.xml',
            '/sitemap_{type:(google)}-{site_id}.xml'
        ), null, t('Sitemap XML'), true);

        return $routes;
    }
}