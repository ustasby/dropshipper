<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace PageSeo\Model\Orm;
use \RS\Orm\Type;

class PageSeo extends \RS\Orm\OrmObject
{
    protected static
        $table = 'page_seo';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'route_id' => new Type\Varchar(array(
                'description' => t('Маршрут'),
                'attr' => array(array('size' => 1)),
                'list' => array(array(__CLASS__, 'getRouteList')),
                'meVisible' => false
            )),
            'meta_title' => new Type\Varchar(array(
                'maxLength' => 1000,
                'description' => t('Заголовок')
            )),
            'meta_keywords' => new Type\Varchar(array(
                'maxLength' => 1000,
                'description' => t('Ключевые слова')
            )),
            'meta_description' => new Type\Varchar(array(
                'maxLength' => 1000,
                'viewAsTextarea' => true,
                'description' => t('Описание')
            ))
        ));
        
        $this->addIndex(array('site_id', 'route_id'), self::INDEX_UNIQUE);
    }
    
    /**
    * Возвращает список маршрутов, для которых можно задать meta теги
    * @return array
    */
    public static function getRouteList()
    {
        $list = array();
        foreach(\RS\Router\Manager::getRoutes() as $key => $route) {
            if (!$route->isHidden()) {
                $list[$key] = $route->getDescription();
            }
        }
        return $list;
    }
    
    /**
    * Возращает объект маршрута
    */
    function getRoute()
    {
        return \RS\Router\Manager::obj()->getRoute($this['route_id']);
    }
}