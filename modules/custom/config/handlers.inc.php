<?php

namespace Custom\Config;

use \RS\Orm\Type as OrmType;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getroute')
            ->bind('getmenus');
    }
    
    /**
    * Получает маршруты для этого модуля
    * 
    * @param array $routes
    * @return \RS\Router\Route[]
    */
    public static function getRoute($routes) 
    {
        return $routes;
    }

    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        return $items;
    }





}