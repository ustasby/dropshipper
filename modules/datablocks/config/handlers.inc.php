<?php
namespace DataBlocks\Config;

/**
* Класс содержит обработчики событий, на которые подписан модуль
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    /**
    * Добавляет подписку на события
    * 
    * @return void
    */
    function init()
    {
        $this
            ->bind('getroute')  //событие сбора маршрутов модулей
            ->bind('getmenus'); //событие сбора пунктов меню для административной панели
    }
    
    /**
    * Возвращает маршруты данного модуля. Откликается на событие getRoute.
    * @param array $routes - массив с объектами маршрутов
    * @return array of \RS\Router\Route
    */
    public static function getRoute(array $routes) 
    {        
        $routes[] = new \RS\Router\Route('datablocks-front-nodeview',
        array(
            '/idata/{alias}/'
        ), null, 'Структуры данных');
        
        return $routes;
    }

    /**
    * Возвращает пункты меню этого модуля в виде массива
    * @param array $items - массив с пунктами меню
    * @return array
    */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => 'Структуры данных',
            'alias' => 'datablocks-ctrl',
            'link' => '%ADMINPATH%/datablocks-ctrl/',
            'parent' => 'website',
            'typelink' => 'link',
        );
        return $items;
    }
}