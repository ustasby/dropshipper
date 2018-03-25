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
* Блок-контроллер Поиск по товарам
*/
class SearchLine extends \RS\Controller\StandartBlock
{
    const
        SORT_RELEVANT = 'relevant';
        
    protected static
        $controller_title = 'Поиск товаров по названию',
        $controller_description = 'Отображает форму для поиска товаров по ключевым словам';

    protected
        $action_var = 'sldo',
        $default_params = array(
            'searchLimit' => 5,
            'searchBrandLimit' => 1,
            'searchCategoryLimit' => 1,
            'hideAutoComplete' => 0,
            'indexTemplate' => 'blocks/searchline/searchform.tpl',
            'imageWidth' => 62,
            'imageHeight' => 62,
            'imageResizeType' => 'xy',
            'order_field' => self::SORT_RELEVANT,
            'order_direction' => 'asc'
        );

    /**
     * @var \Catalog\Model\Api $api
     */
    public $api;
    /**
     * @var \Catalog\Model\SearchLineApi $search_line_api
     */
    public $search_line_api;
                    
    /**
    * Инициализация
    * 
    */
    function init()
    {
        $this->api = new \Catalog\Model\Api();
        $this->search_line_api = new \Catalog\Model\SearchLineApi();
    }

    /**
     * Возвращает параметры блока
     *
     * @return \RS\Orm\AbstractObject
     */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'imageWidth' => new Type\Integer(array(
                'description' => t('Ширина изображения в подсказках'),
                'maxLength' => 6
            )),
            'imageHeight' => new Type\Integer(array(
                'description' => t('Высота изображения в подсказках'),
                'maxLength' => 6
            )),
            'imageResizeType' => new Type\Varchar(array(
                'description' => t('Тип масштабирования изображения в подсказках'),
                'maxLength' => 4,
                'listFromArray' => array(array(
                    'xy' => 'xy',
                    'axy' => 'axy',
                    'cxy' => 'cxy',
                    'ctxy' => 'ctxy',
                ))
            )),
            'hideAutoComplete' => new Type\Integer(array(
                'description' => t('Отключить подсказку результатов поиска в выпадающем списке'),
                'checkboxView' => array(1,0)
            )),
            'searchLimit' => new Type\Integer(array(
                'description' => t('Количество товаров в выпадающем списке')
            )),
            'searchBrandLimit' => new Type\Integer(array(
                'description' => t('Количество брендов в выпадающем списке')
            )),
            'searchCategoryLimit' => new Type\Integer(array(
                'description' => t('Количество категорий в выпадающем списке')
            )),
            'order_field' => new Type\Varchar(array(
                'description' => t('Сортировка результатов среди товаров'),
                'listFromArray' => array(array(
                    self::SORT_RELEVANT => t('Не выбрано'),
                    'dateof' => t('Дата'),
                    'rating' => t('Рейтинг'), 
                    'cost' => t('Цена'),
                    'title' => t('Название'),
                ))
            )),
            'order_direction' => new Type\Varchar(array(
                'description' => t('Направление сортировки среди товаров'),
                'listFromArray' => array(array(
                    'asc' => t('по возрастанию'),
                    'desc' => t('по убыванию')
                ))
            ))
        ));
    }

    /**
     * Метод обработки отображения поисковой строки
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionIndex()
    {             
        $query = trim($this->url->get('query', TYPE_STRING));      
        if ($this->router->getCurrentRoute() && $this->router->getCurrentRoute()->getId() == 'catalog-front-listproducts' && !empty($query)) {
            $this->view->assign('query', $query);
        }
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }

    /**
     * Метод отработки запроса на поиск. Возвращает JSON ответ
     *
     * @return string
     */
    function actionAjaxSearchItems()
    {
        $query = trim($this->url->request('term', TYPE_STRING));
        $result_json = array();

        if (!empty($query)){

            //Найдем подходящие товарам
            /**
             * @var \Catalog\Model\Orm\Product[] $list
             */
            $list = $this->search_line_api->getSearchQueryProductResults($query, $this, $this->getParam('order_field'), $this->getParam('order_direction'), $this->getParam('searchLimit'));

            $shop_config = \RS\Config\Loader::byModule('shop');
            if (!empty($list)) {
                foreach ($list as $product) {

                    $price = ($shop_config->check_quantity && $product->num < 1) ? t('Нет в наличии') : $product->getCost() . ' ' . $product->getCurrency();

                    $result_json[] = array(
                        'value' => $product->title,
                        'label' => preg_replace("#($query)#iu", '<b>$1</b>', $product->title),
                        'barcode' => preg_replace("#($query)#iu", '<b>$1</b>', $product->barcode),
                        'image' => $product->getMainImage()->getUrl($this->getParam('imageWidth'), $this->getParam('imageHeight'), $this->getParam('imageResizeType')),
                        'price' => $price,
                        'type' => 'product',
                        'url' => $product->getUrl()
                    );
                }

                //Секция все результаты товаров
                if ($this->getModuleConfig()->show_all_products){
                    $result_json[] = array(
                        'value' => "",
                        'label' => t("Показать все товары"),
                        'type' => 'search',
                        'url' => $this->router->getUrl('catalog-front-listproducts', array('query' => $query))
                    );
                }

            }

            //Найдем бренды подходящие под запрос
            /**
             * @var \Catalog\Model\Orm\Brand[] $list
             */
            $list = $this->search_line_api->getSearchQueryBrandsResults($query, $this->getParam('searchBrandLimit'));
            if (!empty($list)){
                foreach($list as $brand){
                    $result_json[] = array(
                        'value' => $brand->title,
                        'label' => preg_replace("#($query)#iu", '<b>$1</b>', $brand->title),
                        'image' => $brand->getMainImage()->getUrl($this->getParam('imageWidth'), $this->getParam('imageHeight'), $this->getParam('imageResizeType')),
                        'type' => 'brand',
                        'url' => $brand->getUrl()
                    );
                }
            }

            //Найдем категории подходящие под запрос
            /**
             * @var \Catalog\Model\Orm\Dir[] $list
             */
            $list = $this->search_line_api->getSearchQueryCategoryResults($query, $this->getParam('searchCategoryLimit'));
            if (!empty($list)){
                foreach($list as $dir){
                    $result_json[] = array(
                        'value' => $dir->name,
                        'label' => preg_replace("#($query)#iu", '<b>$1</b>', $dir->name),
                        'image' => $dir->getMainImage()->getUrl($this->getParam('imageWidth'), $this->getParam('imageHeight'), $this->getParam('imageResizeType')),
                        'type' => 'category',
                        'url' => $dir->getUrl()
                    );
                }
            }

        }
        
        $this->app->headers->addHeader('content-type', 'application/json');
        return json_encode($result_json, (defined('JSON_UNESCAPED_UNICODE')) ? JSON_UNESCAPED_UNICODE : 0);
    }
}