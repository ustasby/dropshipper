<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Block;
use \RS\Orm;
use \RS\Site\Manager as SiteManager;

/**
* Контроллер - топ товаров из указанных категорий
* @ingroup Catalog
*/
class TopProducts extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Продукты из категории',
        $controller_description = 'Отображает нужное количество товаров из заданной категории';
    
    protected 
        $default_params = array(
            'indexTemplate' => 'blocks/topproducts/top_products.tpl', //Должен быть задан у наследника
            'pageSize' => 15
        ),
        $page;
        
    public 
        $dirapi,
        $api;
    
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'pageSize' => new Orm\Type\Integer(array(
                'description' => t('Количество элементов на страницу'),
            )),
            'dirs' => new Orm\Type\Varchar(array(
                'description' => t('Категория, из которой выводить товары'),
                'list' => array(array('\Catalog\Model\DirApi', 'selectList')),
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
                    'rating DESC' => t('Рейтинг обратн. порядок'),
                    
                    'rand()' => t('Случайный порядок')
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
        $this->dirapi = \Catalog\Model\Dirapi::getInstance();
    }
    
    function actionIndex()
    {
        $route = \RS\Router\Manager::getRoute('main.index');
        
        $pageSize = $this->getParam('pageSize', null);
        $order    = $this->getParam('order', null);
        $page     = $this->myGet('page', TYPE_INTEGER, 1);
        $in_stock = $this->getParam('only_in_stock',0) || $this->getModuleConfig()->hide_unobtainable_goods == 'Y';
        
        $dir_ids = (array)$this->getParam('dirs');
        $dir_ids = $this->dirapi->convertAliasesToId($dir_ids);
        
        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('add', array('dir' => $dir_ids[0]), 'catalog-ctrl');
            $debug_group->addDebugAction(new \RS\Debug\Action\Create($create_href));
            $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
        }
        
        if (!empty($dir_ids)) {
            $ids_with_childs = \RS\Cache\Manager::obj()
                                    ->request(array($this->dirapi, 'FindSubFolder'), $dir_ids, SiteManager::getSiteId());
            
            $this->api->setFilter('dir', $ids_with_childs, 'in');
            $this->api->setFilter('public', 1);
            if ($in_stock) { //Если показывать только в наличии
               $this->api->setFilter('num', 0, '>'); 
            }
            $total = $this->api->getListCount();
                        
            if (!empty($order)){
                $this->api->setOrder($order); 
            } 
            $this->api->setGroup('id');
            $paginator = new \RS\Helper\Paginator($page, $this->api->getListCount(), $pageSize, \RS\Helper\Paginator::PATTERN_KEYREPLACE, array(), 'page');
            $products = $this->api->getList($page, $pageSize);
            $products = $this->api->addProductsPhotos($products);
            $products = $this->api->addProductsDirs($products);
            $products = $this->api->addProductsCost($products);
            $this->view->assign(array(
                'dir' => new \Catalog\Model\Orm\Dir($dir_ids[0]),
                'paginator' => $paginator,
                'products' => $products,
                'page' => $page,
            ));
        }
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}