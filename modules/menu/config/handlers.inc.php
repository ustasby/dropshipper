<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Config;
use \RS\Router;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('getpages')
            ->bind('menu.gettypes');
    }
    
    public static function getRoute($routes) 
    {
        if (\RS\Site\Manager::getSite() !== false) {
            $api = new \Menu\Model\Api();
            $api->setFilter('menutype', 'user');
            $list = $api->getList();
            
            foreach($list as $item) {
                if ($route = $item->getTypeObject()->getRoute()) {
                    $routes[] = $route;
                }
            }
            
            return $routes;
        }
    }
    
    public static function getPages($urls)
    {
        $api = new \Menu\Model\Api();
        $api->setFilter('public', 1);
        $api->setFilter('hide_from_url', 0);
        $api->setFilter('menutype', 'user');
        $list = $api->getList();
        $local_urls = array();
        foreach($list as $item) {
            $url = $item->getHref();
            $local_urls[$url] = array(
                'loc' => $url
            );
        }
        $urls = array_merge($urls, array_values($local_urls));
        return $urls;
    }
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('Веб-сайт'),
                'alias' => 'website',
                'link' => '%ADMINPATH%/menu-ctrl/',
                'sortn' => 30,
                'typelink' => 'link',
                'parent' => 0
            );
        $items[] = array(
                'title' => t('Управление'),
                'alias' => 'control',
                'link' => '%ADMINPATH%/main-options/',
                'sortn' => 40,
                'typelink' => 'link',
                'parent' => 0
            );
        $items[] = array(
                'title' => t('Разное'),
                'alias' => 'modules',
                'link' => 'JavaScript:;',
                'sortn' => 50,
                'typelink' => 'link',
                'parent' => 0
            );            
        $items[] = array(
                'title' => t('Меню'),
                'alias' => 'menu',
                'link' => '%ADMINPATH%/menu-ctrl/',
                'parent' => 'website',
                'sortn' => 0,
                'typelink' => 'link',
                'parent' => 'website'
            );            
        $items[] = array(
                'title' => t('Пользователи'),
                'alias' => 'userscontrol',
                'link' => '%ADMINPATH%/users-ctrl/',
                'sortn' => 6,
                'parent' => 'control',
                'typelink' => 'link',
                'parent' => 'control'
            );            
        
        return $items;
    }
    
    /**
    * Возвращает список пунктов меню
    * 
    * @param \Menu\Model\MenuType\AbstractType[] $menu_types
    * @return \Menu\Model\MenuType\AbstractType[]
    */
    public static function menuGetTypes($menu_types)
    {
        $menu_types[] = new \Menu\Model\MenuType\Article();
        $menu_types[] = new \Menu\Model\MenuType\Link();
        $menu_types[] = new \Menu\Model\MenuType\Page();
        $menu_types[] = new \Menu\Model\MenuType\Separator();
        
        return $menu_types;
    }
}

