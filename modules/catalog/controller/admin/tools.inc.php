<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Admin;

/**
* Содержит действия по обслуживанию
*/
class Tools extends \RS\Controller\Admin\Front
{
    /**
     * Удаление несвязанных характеристик
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxCleanProperty()
    {
        $api = new \Catalog\Model\PropertyApi();
        $count = $api->cleanUnusedProperty();
        
        return $this->result->setSuccess(true)->addMessage(t('Удалено %0 характеристик', array($count)));
    }

    /**
     * Добавление ЧПУ товарам и категориям
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxCheckAliases()
    {
        $api = new \Catalog\Model\Api();
        $product_count = $api->addTranslitAliases();

        $dir_api = new \Catalog\Model\Dirapi();
        $dir_count = $dir_api->addTranslitAliases();
        
        return $this->result->setSuccess(true)->addMessage(t('Обновлено %0 товаров, %1 категорий', array($product_count, $dir_count)));
    }

    /**
     * Добавление ЧПУ брендам
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxCheckBrandsAliases()
    {
        $api = new \Catalog\Model\BrandApi();
        $brands_count = $api->addTranslitAliases();

        return $this->result->setSuccess(true)->addMessage(t('Обновлено %0 брендов', array($brands_count)));
    }

    /**
     * Удаляет несвязанные комплектации
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxCleanOffers()
    {
        $api = new \Catalog\Model\OfferApi();
        $delete_count = $api->cleanUnusedOffers();
        
        return $this->result->setSuccess(true)->addMessage(t('Удалено %0 комплектаций', array($delete_count)));
    }

    /**
     * Переиндексация товаров
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionajaxReIndexProducts()
    {
        $config = $this->getModuleConfig();
        $property_index = in_array('properties', $config['search_fields']);
        
        $api = new \Catalog\Model\Api();
        $count = 0;
        $page = 1;
        while($list = $api->getList($page, 200)) {
            if ($property_index) {
                $list = $api->addProductsProperty($list);
            }
            
            foreach($list as $product) {
                $product->updateSearchIndex();
            }
            $count += count($list);
            $page++;
        }
        
        return $this->result->setSuccess(true)->addMessage(t('Обновлено %0 товаров', array($count)));
    }
    
    /**
    * Сброс всех хешей импорта
    *
    * @return \RS\Controller\Result\Standard
    */
    function actionAjaxCleanImportHash()
    {
        // товары
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Product())
            ->set(array('import_hash' => null))
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
            ->exec();
        // комплектации
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Offer())
            ->set(array('import_hash' => null))
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
            ->exec();
        
        return $this->result->setSuccess(true)->addMessage(t('Хеши импорта удалены'));
    }
}