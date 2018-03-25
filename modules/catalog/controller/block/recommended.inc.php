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
* Блок-контроллер Список категорий
*/
class Recommended extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title       = 'Рекомендуемые товары',
        $controller_description = 'Отображает товары, отмеченные как рекомендуемые';

    protected
        $default_params = array(
            'indexTemplate' => 'blocks/recommended/recommended.tpl',
        );    
        
    
    /**
    * Возвращает дополнительные параметры
    * 
    */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'random' => new Type\Integer(array(
                'description' => t('Показывать в случайном порядке'),
                'default' => 0,
                'checkboxView' => array(1,0)
            )),
            'in_stock' => new Type\Integer(array(
                'description' => t('Показывать только те что в наличии'),
                'default' => 0,
                'checkboxView' => array(1,0)
            )),
        ));
    }

    /**
     * @var \Catalog\Model\Dirapi $dirapi
     */
    public $dirapi;
    /**
     * @var \Catalog\Model\Api $api
     */
    public $api;
        
    function init()
    {
        $this->api = new \Catalog\Model\Api();
        $this->dirapi = \Catalog\Model\Dirapi::getInstance();
    }                    
    
    function actionIndex()
    {
        $route = \RS\Router\Manager::obj()->getCurrentRoute();
        if ($route->getId() == 'catalog-front-product') {
            if (isset($route->product)) {
                $products = $route->product->getRecommended();
                if ($this->getParam('random')){
                    shuffle($products);
                }
                
                if ($this->getParam('in_stock')){
                    $arr = array();
                    foreach ($products as $product){
                        if ($product['num']>0){
                            $arr[] = $product;    
                        }
                    }
                    $products = $arr;
                }
                
                $this->view->assign(array(
                    'current_product' => $route->product,
                    'recommended' => $products,
                    'recommended_title' => 'Рекомендуемые товары'
                ));
            }
        }
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }

}