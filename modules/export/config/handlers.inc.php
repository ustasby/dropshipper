<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Config;
use \RS\Event\Manager as EventManager;
use \RS\Router;
use \RS\Orm\Type as OrmType;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('export.gettypes');
            
    }
    
    /**
    * Возвращает маршруты данного модуля
    */
    public static function getRoute(array $routes) 
    {        
        $routes[] = new \RS\Router\Route('export-front-gate', array(
            '/site{site_id}/export-{export_type}-{export_id}.xml',
            '/site{site_id}/export-{export_type}-{export_id}/',
        ), null, t('Шлюз экспорта данных'), true);
        
        return $routes;
    }
    
    
    /**
    * Возвращает список доступных типов экспорта
    */
    public static function exportGetTypes($list)
    {
        $list[] = new \Export\Model\ExportType\Yandex\Yandex();
        $list[] = new \Export\Model\ExportType\MailRu\MailRu();
        $list[] = new \Export\Model\ExportType\Google\Google();
        $list[] = new \Export\Model\ExportType\Avito\Avito();
        return $list;
    }
    
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'typelink'  => 'separator',
                'alias'     => 'products_separator',
                'parent'    => 'products',
                'sortn'     => 6
            );
        $items[] = array(
                'title'     => t('Экспорт данных'),
                'alias'     => 'export',
                'link'      => '%ADMINPATH%/export-ctrl/',
                'typelink'  => 'link',
                'parent'    => 'products',
                'sortn'     => 7
            );
        return $items;
    }
}