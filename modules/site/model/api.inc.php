<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Site\Model;

class Api extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Site\Model\Orm\Site,
        array(
            'sortField' => 'sortn',
            'defaultOrder' => 'sortn',
            'nameField' => 'title'
        ));
    }
    
    /**
    * Удаляет у удаляемого сайта всю привязанную к сайту информацию со всех используемых таблиц
    * 
    * @param integer $site_id - id сайта
    */ 
    function deleteSiteNoNeedInfo($site_id){
      //Удаляем склады привязанные к сайту
      $warehouse_api = new \Catalog\Model\WareHouseApi();
      $warehouses = $warehouse_api->setFilter('site_id',$site_id)->getList();
      if (!empty($warehouses)){
          foreach ($warehouses as $warehouse){
             $warehouse->delete(); 
          }
      }  
      unset($warehouses);
      
      //Удаляем категории товаров и товары соотвественно
      $dir_api = new \Catalog\Model\Dirapi();
      $dirs    = $dir_api->setFilter('site_id',$site_id)->getList();
      if (!empty($dirs)){
         foreach ($dirs as $dir){
            $dir->delete();
         } 
      }
      unset($dirs);
      
      //Удаляем валюты и курсы валют
      $currency_api = new \Catalog\Model\CurrencyApi();
      $currencies   = $currency_api->setFilter('site_id',$site_id)->getList();
      foreach($currencies as $currency){
         $currency->delete(); 
      }
      unset($currencies);
      
      
      //Удаляем бренды
      $brand_api = new \Catalog\Model\BrandApi();
      $brands   = $brand_api->setFilter('site_id',$site_id)->getList();
      foreach($brands as $brand){
         $brand->delete(); 
      }
      unset($brands);
      
      //Удаляем купивших в 1 клик
      $oneclick_api = new \Catalog\Model\OneClickItemApi();
      $oneclicks   = $oneclick_api->setFilter('site_id',$site_id)->getList();
      foreach($oneclicks as $oneclick){
         $oneclick->delete(); 
      }
      unset($oneclicks);
      
      //Удаляем типы цены
      $typecost_api = new \Catalog\Model\CostApi();
      $typecosts   = $typecost_api->setFilter('site_id',$site_id)->getList();
      foreach($typecosts as $typecost){
         $typecost->delete(); 
      }
      unset($typecosts);
      
      //Удаляем единицы измерений
      $unit_api = new \Catalog\Model\UnitApi();
      $units    = $unit_api->setFilter('site_id',$site_id)->getList();
      foreach($units as $unit){
         $unit->delete(); 
      }
      unset($units);   
      
      //Удаляем директории характеристик
      $propdirs_api = new \Catalog\Model\PropertyDirApi();
      $propdirs    = $propdirs_api->setFilter('site_id',$site_id)->getList();
      foreach($propdirs as $propdir){
         $propdir->delete(); 
      }
      unset($propdirs); 
      
      //Удаляем характеристики
      $props_api = new \Catalog\Model\PropertyApi();
      $props    = $props_api->setFilter('site_id',$site_id)->getList();
      foreach($props as $prop){
         $prop->delete(); 
      }
      unset($props); 
      
      //Удаляем категории статей и статьи
      $arctilecat_api = new \Article\Model\Catapi();
      $arctilecats    = $arctilecat_api->setFilter('site_id',$site_id)->getList();
      foreach($arctilecats as $arctilecat){
         $arctilecat->delete(); 
      }
      unset($arctilecats); 
      
     
      //Удаляет баннеры
      $banners_api = new \Banners\Model\BannerApi();
      $banners    = $banners_api->setFilter('site_id',$site_id)->getList();
      foreach($banners as $banner){
         $banner->delete(); 
      }
      unset($banners);
      
      //Удаляем комментарии
      $comments_api = new \Comments\Model\Api();
      $comments    = $comments_api->setFilter('site_id',$site_id)->getList();
      foreach($comments as $comment){
         $comment->delete(); 
      }
      unset($comments);
      
      //Удаляем всё что связано с формами
      $forms_api = new \Feedback\Model\FormApi();
      $forms     = $forms_api->setFilter('site_id',$site_id)->getList();
      foreach($forms as $form){
         $form->delete(); 
      }
      unset($forms);
      
      $formfield_api = new \Feedback\Model\FieldApi();
      $formfields    = $formfield_api->setFilter('site_id',$site_id)->getList();
      foreach($formfields as $formfield){
         $formfield->delete(); 
      }
      unset($formfields);
      
      $formresult_api = new \Feedback\Model\ResultApi();
      $formresults    = $formresult_api->setFilter('site_id',$site_id)->getList();
      foreach($formresults as $formresult){
         $formresult->delete(); 
      }
      unset($formresults);
      
    }
}

