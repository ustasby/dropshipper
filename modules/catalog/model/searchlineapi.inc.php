<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

/**
* API для запросов поисковой строки
*/
class SearchLineApi
{
    /**
     * @var \Catalog\Model\Api $api
     */
    public $api;
    /**
     * @var \Catalog\Model\Dirapi $dirapi
     */
    public $dirapi;
    /**
     * @var \Catalog\Model\BrandApi $dirapi
     */
    public $brand_api;

    function __construct()
    {
        $this->api       = new \Catalog\Model\Api();
        $this->dirapi    = new \Catalog\Model\Dirapi();
        $this->brand_api = new \Catalog\Model\BrandApi();
    }

    /**
     * Возвращает результаты поиска по категориям в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param integer $limit - лимит результатов поиска
     *
     * @return \Catalog\Model\Orm\Dir[]
     */
    function getSearchQueryCategoryResults($query, $limit = 1)
    {
        $list = $this->dirapi->setFilter('name', "%$query%",'like')
            ->getList(1, $limit);

        //Если не нашли результаты, то посмотрим странслитом
        if (empty($list)){
            $query = \RS\Helper\Transliteration::puntoSwitchWord($query);
            $list = $this->dirapi->clearFilter()->setFilter('name', "%$query%",'like')
                ->getList(1, $limit);
        }
        return $list;
    }

    /**
     * Возвращает результаты поиска по брендам в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param integer $limit - лимит результатов поиска
     *
     * @return \Catalog\Model\Orm\Brand[]
     */
    function getSearchQueryBrandsResults($query, $limit = 1)
    {
        $list = $this->brand_api->setFilter('title', "%$query%",'like')
                           ->getList(1, $limit);

        //Если не нашли результаты, то посмотрим странслитом
        if (empty($list)){
            $query = \RS\Helper\Transliteration::puntoSwitchWord($query);
            $list = $this->brand_api->clearFilter()->setFilter('title', "%$query%",'like')
                               ->getList(1, $limit);
        }
        return $list;
    }

    /**
     * Возвращает результаты поиска по товаром в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param \Catalog\Controller\Block\SearchLine $controller - строка для поиска
     * @param integer $limit - лимит результатов поиска
     * @param string $order_field - колонка для сортировки
     * @param string $order_direction - лимит результатов поиска
     * @return \Catalog\Model\Orm\Product[]
     */
    function getSearchQueryProductResults($query, $controller, $order_field, $order_direction, $limit = 1)
    {
        $q = $this->api->queryObj();
        $q->select = 'A.*';

        $search = \Search\Model\SearchApi::currentEngine();
        $search->setFilter('B.result_class', 'Catalog\Model\Orm\Product');
        $search->setQuery($query);
        $search->joinQuery($q);

        $this->api->setFilter('public', 1);

        if ($order_field != \Catalog\Controller\Block\SearchLine::SORT_RELEVANT) {
            $this->api->setSortOrder($order_field, $order_direction);
        }

        if (\RS\Config\Loader::byModule($this)->hide_unobtainable_goods == 'Y') {
            $this->api->setFilter('num', '0', '>');
        }

        \RS\Event\Manager::fire('init.api.catalog-front-listproducts', $controller);

        //Найдем товары
        $list = $this->api->getList(1, $limit);
        $list = $this->api->addProductsPhotos($list);
        $list = $this->api->addProductsCost($list);

        return $list;
    }
    
}