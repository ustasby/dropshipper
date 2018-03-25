<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Front;

/**
* Контроллер отвечает за просмотр брэнда
*/
class Brand extends \RS\Controller\Front
{            
    public
        $api,
        $brand_api,
        $config;
        
    function init()
    {
        $this->api = new \Catalog\Model\Api(); 
        $this->brand_api = new \Catalog\Model\BrandApi();
    }
    
    function actionIndex()
    {
        $id = urldecode( $this->url->get('id', TYPE_STRING) );
                
        $brand = $this->brand_api->getById($id,"alias");
        
        if (!$brand) $this->e404(t('Бренд с таким именем не найден'));
        //Если есть alias и открыта страница с id вместо alias, то редирект
        $this->checkRedirectToAliasUrl($id, $brand, $brand->getUrl());

        $this->router->getCurrentRoute()->brand_id = $brand['id']; //Сообщаем системе, id просматриваемой статьи
              
        //Хлебные крошки
        $this->app->breadcrumbs
            ->addBreadCrumb(t("Бренды"), $this->router->getUrl('catalog-front-allbrands'))
            ->addBreadCrumb($brand['title']);
        
        $this->app->title->addSection($brand['meta_title'] ? $brand['meta_title'] : $brand['title']);
        $this->app->meta->addKeywords($brand['meta_keywords']);    
        $this->app->meta->addDescriptions($brand['meta_description']);
        
        //Получим директории в которых есть товар с заданным производителем
        $dirs   = $this->brand_api->getBrandDirs($brand);
        
        //Получим товары из спец. категорий принадлежащих этому бренду
        $limit    = $this->getModuleConfig()->brand_products_cnt;
        $products = $this->brand_api->getProductsInSpecDirs($brand, $limit);
        
        if (!empty($products)){
            //Загружаем только фото и цены, остальные сведения, если нужны нужно подгружать в шаблоне
           $products = $this->api->addProductsPhotos($products);
           $products = $this->api->addProductsCost($products);
        }
        
        $this->view->assign(array(
            'products' => $products,         //Товары бренда в спец. категориях
            'brand' => $brand,               //Бренд
            'dirs' => $dirs                 //Категории бренда
        ));
        
        return $this->result->setTemplate('brand.tpl');
    }
}
