<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Block;
use \RS\Orm\Type;

/**
* Контроллер блока последних просмотренных товаров
* @ingroup Catalog
*/
class LastViewed extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Просмотренные раннее товары',
        $controller_description = 'Блок со списком просмотренных раннее товаров пользователя';
        
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/lastviewed/products.tpl', //Должен быть задан у наследника
            'pageSize' => 16
        ),
        $products;
        
    public 
        $log_api,
        $api; 
        
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'pageSize' => new Type\Integer(array(
                'description' => t('Количество отображаемых элементов')
            ))
        ));
    }
        
    function init()
    {
        $this->log_api = new \Users\Model\LogApi();
        $this->api = new \Catalog\Model\Api();
    }
    
    function actionIndex()
    {
        $products = $this->makeList();
        $this->view->assign(array(
            'products' => $products
        ));
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
    
    
    protected function makeList()
    {
        $list = $this->log_api->getLogItems('Catalog\Model\Logtype\ShowProduct', $this->getParam('pageSize', 16), 0, null);        
        $products = array();
        $products_id = array();
        foreach($list as $event) {
            $products_id[] = $event->getObjectId();
        }
        
        if (!empty($products_id)) {
            //Загружает сразу группу товаров, подгружает разом категории и фото ко всем товарам
            $this->api->setFilter('id', $products_id, 'in');
            $products = $this->api->getList();
            $products = $this->api->addProductsPhotos($products);            
        }
        $this->products = $products;
        return $products;
    }

}