<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;

/**
* Фронт контроллер Кастомного выбора комплектаций с последующей покупкой
*/
class MultiOffers extends \RS\Controller\Front
{
        
    function init()
    {
       $this->api      = new \Catalog\Model\Api(); 
    }
    
    /**
    * Открытие окна с комплектациями товара
    */ 
    function actionIndex()
    {
       $product_id  = $this->url->request('product_id',TYPE_INTEGER,0); //Id товара
       $offers      = array(); //Товарные комплектации 
       $multioffers = array(); //Многомерные комплектации 
       $product     = array(); //Сам товар
       
       if ($product_id){
           $product = new \Catalog\Model\Orm\Product($product_id);
           if ($product){
              $offers      = $product->fillOffers();       //Получим все комплектации товара  
              $multioffers = $product->fillMultiOffers();  //Получим все мног. коплектации товара  
           } 
       } 
       //Добавим товар в текущий маршрут
       $this->router->getCurrentRoute()->product = $product;
       
       $this->view->assign(array(
          'product'     => $product,  
          'offers'      => $offers,  
          'multioffers' => $multioffers,  
       ));
       return $this->result->setTemplate('show_complekts.tpl'); 
    }
}
    