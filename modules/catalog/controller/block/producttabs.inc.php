<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Block;
use \RS\Orm;

/**
* Контроллер - топ товаров из указанных категорий
* @ingroup Catalog
*/
class ProductTabs extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Товары из нескольких категорий',
        $controller_description = 'Отображает товары, распределенные по закладкам, соответствующим названию категории';
    
    protected 
        $default_params = array(
            'indexTemplate' => 'blocks/producttabs/producttabs.tpl', //Должен быть задан у наследника
            'pageSize' => 4
        ),
        $page;
        
    public 
        $dirapi,
        $api;
    
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'pageSize' => new Orm\Type\Integer(array(
                'description' => t('Количество элементов в закладке'),
            )),
            'categories' => new Orm\Type\ArrayList(array(
                    'description' => t('Товары каких спецкатегорий показывать?'),
                    'list' => array(array('\Catalog\Model\DirApi', 'selectList'), false),
                    'attr' => array(array(
                        'multiple' => 'multiple',
                        'size' => 10
                    ))
                )),
            'order' => new Orm\Type\Varchar(array(
                'description' => t('Поле сортировки'),
                'listFromArray' => array(array(
                    'id' => 'ID',
                    'title' => t('Название'),
                    'dateof' => t('Дата'),
                    'rating' => t('Рейтинг'),
                    
                    'id DESC' => t('ID обратн. порядок'),
                    'title DESC' => t('Название обратн. порядок'),
                    'dateof DESC' => t('Дата обратн. порядок'),
                    'rating DESC' => t('Рейтинг обратн. порядок')
                ))
            )),
            'only_in_stock' => new Orm\Type\Integer(array(
                'default' => 0,
                'description' => t('Показывать только те, что в наличии?'),
                'CheckboxView' => array(1, 0),
            )),
        ));        
    }
    
    
    function init()
    {
        $this->api = new \Catalog\Model\Api();
        $this->dirapi = new \Catalog\Model\Dirapi();
    }
    
    function actionIndex()
    {
        $route = \RS\Router\Manager::getRoute('main.index');

        $ids_or_aliases = $this->getParam('categories');
        $ids = array();
        foreach($ids_or_aliases as $some) {
            $ids[] = is_numeric($some) ? $some : (int)\Catalog\Model\Orm\Dir::loadByWhere(array('alias' => $some))->id;
        }
              
        if (!empty($ids)) {
            $this->dirapi->setFilter('id', $ids, 'in');
            $this->dirapi->setOrder('FIELD(id, '.implode(',', \RS\Helper\Tools::arrayQuote($ids)).')');
            $dirs = $this->dirapi->getAssocList('id');
            
            $products_by_dirs = array();
            $catalog_config = \RS\Config\Loader::byModule('catalog'); //Получим конфиг
            $in_stock = $this->getParam('only_in_stock',0) || $catalog_config['hide_unobtainable_goods']=='Y';
            foreach($ids as $dir_id) {
                $q = $this->api
                           ->clearFilter()
                           ->setFilter('dir', $dir_id)
                           ->setFilter('public', 1);
                if ($in_stock) { //Если показывать только в наличии
                   $q->setFilter('num',0,'>'); 
                }
                $products_by_dirs[$dir_id] = $q->getList(1, $this->getParam('pageSize'), $this->getParam('order', 'id DESC'));
                $products_by_dirs[$dir_id] = $this->api->addProductsDirs($products_by_dirs[$dir_id]);
            }
            
            $this->view->assign(array(
                'dirs' => $dirs,
                'products_by_dirs' => $products_by_dirs,
            ));
        }        
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}