<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Config;
use Catalog\Model\OneClickItemApi;
use RS\Config\Loader;
use RS\Module\Manager;
use \RS\Orm\Type as OrmType;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
* @ingroup Catalog
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getroute')
            ->bind('orm.init.users-user')
            ->bind('orm.beforewrite.users-user')
            ->bind('user.auth')
            ->bind('comments.gettypes')
            ->bind('getmenus')
            ->bind('getpages')
            ->bind('meter.recalculate')
            ->bind('cron');
            
        if (\Setup::$INSTALLED) {
            $this->bind('orm.afterwrite.site-site', $this, 'onSiteCreate');
        }
    }

    /**
     * Возвращает счетчик непросмотренных объектов
     */
    public static function meterRecalculate($meters)
    {
        $oneclick_api = new OneClickItemApi();
        $oneclick_meter_api = $oneclick_api->getMeterApi();
        $meters[$oneclick_meter_api->getMeterId()] = $oneclick_meter_api->getUnviewedCounter();

        return $meters;
    }

    /**
    * Возвращает массив маршрутов для системы
    * 
    * @param \RS\Router\Route[] $routes - массив установленных ранее маршрутов
    * @return \RS\Router\Route[]
    */
    public static function getRoute($routes) 
    {
        //Просмотр категории продукции
        $routes[] = new \RS\Router\Route('catalog-front-listproducts', array(
            '/catalog/{category}/',
            '/catalog/'
        ), null, t('Просмотр категории продукции'));
        
        //Карточка товара
        $routes[] = new \RS\Router\Route('catalog-front-product', 
            '/product/{id}/', null, t('Карточка товара'));
        
        //Сравнение товаров    
        $routes[] = new \RS\Router\Route('catalog-front-compare', 
            '/compare/', null, t('Сравнение товаров'));
            
        //Избранные товары  
        $routes[] = new \RS\Router\Route('catalog-front-favorite', 
            '/favorite/', null, t('Избранные товары'));    
        
        //Обработка страницы купить в 1 клик, добавление маршрута    
        $routes[] = new \RS\Router\Route('catalog-front-oneclick', 
            '/oneclick/{product_id}/', null, t('Купить в один клик'));
            
        //Отображение всех брендов в алфавитном порядке
        $routes[] = new \RS\Router\Route('catalog-front-allbrands', 
            '/brand/all/', null, t('Список брендов'));
        
        //Отображение отдельно брендов
        $routes[] = new \RS\Router\Route('catalog-front-brand', 
            '/brand/{id}/', null, t('Просмотр отдельного бренда'));
            
        //Отображение отдельно склада
        $routes[] = new \RS\Router\Route('catalog-front-warehouse', 
            '/warehouse/{id}/', null, t('Просмотр отдельного склада'));
            
        
        
        return $routes;
    }    
    
    public static function onSiteCreate($params)
    {
        if ($params['flag'] == \RS\Orm\AbstractObject::INSERT_FLAG) {
            //Добавляем цену по-умолчанию
            $site = $params['orm'];
            $defaultCost = new \Catalog\Model\Orm\Typecost();
            $defaultCost->getFromArray(array(
                'site_id' => $site['id'],
                'title' => t('Розничная'),
                'type' => 'manual',
                'round' => \Catalog\Model\Orm\Typecost::ROUND_DISABLED
            ))->insert();
            
            //Добавляем валюту по-умолчанию
            $defaultCurrency = new \Catalog\Model\Orm\Currency();
            $defaultCurrency->getFromArray(array(
                'site_id' => $site['id'],
                'title' => 'RUB',
                'stitle' => t('р.'),
                'is_base' => 1,
                'ratio' => 1,
                'public' => 1,
                'default' => 1
            ))->insert();

            
            $catalog_config = \RS\Config\Loader::byModule('catalog', $site['id']);
            if ($catalog_config){
               $catalog_config['default_cost'] = $defaultCost['id'];
               $catalog_config->update(); 
            }
            
            $module = new \RS\Module\Item('catalog');
            $installer = $module->getInstallInstance();
            $installer->importCsv(new \Catalog\Model\CsvSchema\Warehouse(), 'warehouse', $site['id']);
        }
    }
    
    /**
    * Расширяем объект User, добавляя в него доп свойство - тип цены
    * 
    * @param \Users\Model\Orm\User $user
    */
    public static function ormInitUsersUser(\Users\Model\Orm\User $user)
    {
        $user->getPropertyIterator()->append(array(
            t('Настройка цен'),
                'user_cost' => new OrmType\ArrayList(array(
                    'description' => t('Персональная цена'),
                    'template'  => '%catalog%/form/user/personal_price.tpl'
                )),
                'cost_id' => new OrmType\Varchar(array(
                    'description' => t('Персональная цена'),
                    'visible'   => false, 
                    'maxLength'  => 1000,
                )),
        ));
    }
    
    /**
    * Функция срабытывает перед сохранением пользователя
    * Сериализует массив c ценами сайтов для поля cost_id
    * 
    * @param array $user_array - массив с параметра
    */
    public static function ormBeforeWriteUsersUser($user_array)
    {
       $flag = $user_array['flag'];
      
       /**
       * @var \Users\Model\Orm\User
       */ 
       $user = $user_array['orm'];
       
       if ($user->isModified('user_cost')) {
          $user['cost_id'] = serialize($user['user_cost']);
       }
    }

    /**
     * Действия после авторизации пользователя
     */
    public static function userAuth() {
        \Catalog\Model\FavoriteApi::mergeFavorites();
    }

    /**
     * Добавляет новые страницы в Sitemap
     *
     * @param array $urls - массив адресов из sitemap
     * @return array
     */
    public static function getPages($urls)
    {
        //Добавим страницы из категорий товаров в sitemap
        $api = new \Catalog\Model\DirApi();
        $api->setFilter('public', 1);
        $page = 1;
        while($list = $api->getList($page, 100)) {
            $page++;
            foreach($list as $product) {
                $urls[] = array(
                    'loc' => $product->getUrl(),
                    'priority' => '0.7'
                );
            }
        }
        
        //Добавим страницы из каталога товаров в sitemap
        $api = new \Catalog\Model\Api();
        $config = \RS\Config\Loader::byModule(__CLASS__);
        $api->setFilter('public', 1);
        if ($config['hide_unobtainable_goods'] == 'Y') {
            $api->setFilter('num', '0', '>');
        }
        $page = 1;
        while($list = $api->getList($page, 100)) {
            $page++;
            foreach($list as $product) {
                $one_url = array(
                    'loc' => $product->getUrl(),
                    'priority' => '0.5'
                );
                foreach ($product->getImages() as $image) {
                    $one_url[] = array(
                        \Sitemap\Model\Api::ELEMENT_NAME_KEY => 'image:image',
                        \Sitemap\Model\Api::ELEMENT_MAP_TYPE_KEY => 'google',
                        'image:loc' => $image->getUrl(800, 800, 'xy', true)
                    );
                }
                $urls[] = $one_url;
            }
        }
        
        //Добавим страницы брендов в sitemap
        $api = new \Catalog\Model\BrandApi();
        $api->setFilter('public', 1);
        $page = 1;
        while($list = $api->getList($page, 100)) {
            $page++;
            foreach($list as $brand) {
                $urls[] = array(
                    'loc' => $brand->getUrl()
                );
            }
        }
        
        //Добавим страницы складов в sitemap
        $api = new \Catalog\Model\WareHouseApi();
        $api->setFilter('public', 1);
        $api->setFilter('use_in_sitemap', 1);
        $page = 1;
        while($list = $api->getList($page, 100)) {
            $page++;
            foreach($list as $warehouse) {
                $urls[] = array(
                    'loc' => $warehouse->getUrl()
                );
            }
        }
        
        return $urls;
    }
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('Товары'),
                'alias' => 'products',
                'link' => '%ADMINPATH%/catalog-ctrl/',
                'sortn' => 20,
                'typelink' => 'link',
                'parent' => 0
            );        
        $items[] = array(
                'title' => t('Каталог товаров'),
                'alias' => 'catalog',
                'link' => '%ADMINPATH%/catalog-ctrl/',
                'sortn' => 0,
                'typelink' => 'link',                   
                'parent' => 'products'
            );
        $items[] = array(
                'title' => t('Характеристики'),
                'alias' => 'property',
                'link' => '%ADMINPATH%/catalog-propctrl/',
                'sortn' => 1,
                'typelink' => 'link',                        
                'parent' => 'products'
            );
        $items[] = array(
                'title' => t('Склады'),
                'alias' => 'warehouse',
                'link' => '%ADMINPATH%/catalog-warehousectrl/',
                'typelink' => 'link',
                'sortn' => 2,              
                'parent' => 'products'
            );            
        $items[] = array(
                'title' => t('Бренды'),
                'alias' => 'brand',
                'link' => '%ADMINPATH%/catalog-brandctrl/', //здесь %ADMINPATH% - URL админ. панели; shoplist - модуль; control - класс фронт контроллера
                'typelink' => 'link', //Тип пункта меню - ссылка
                'sortn' => 2,            
                'parent' => 'products'
            );
        $items[] = array(
                'title' => t('Справочник цен'),
                'alias' => 'costs',
                'link' => '%ADMINPATH%/catalog-costctrl/',
                'sortn' => 3,
                'typelink' => 'link',                      
                'parent' => 'products'
            );
        $items[] = array(
                'title' => t('Единицы измерения'),
                'alias' => 'unit',
                'link' => '%ADMINPATH%/catalog-unitctrl/',
                'sortn' => 4,
                'typelink' => 'link',                     
                'parent' => 'products'
            );
        $items[] = array(
                'title' => t('Валюты'),
                'alias' => 'currency',
                'link' => '%ADMINPATH%/catalog-currencyctrl/',
                'sortn' => 5,
                'typelink' => 'link',                      
                'parent' => 'products'
            );

        $shop_module_exists = Manager::staticModuleExists('shop');

        if ($shop_module_exists) {
            $items[] = array(
                'typelink' => 'separator',
                'alias' => 'oneclick_separator',
                'sortn' => 14,
                'parent' => 'products'
            );
        }

        $items[] = array(
                'title' => t('Покупки в 1 клик'),
                'alias' => 'oneclick',
                'link' => '%ADMINPATH%/catalog-oneclickctrl/',
                'sortn' => $shop_module_exists ? 2 : 15,
                'typelink' => 'link',                     
                'parent' => $shop_module_exists ? 'orders' : 'products'
            );
        return $items;
    }
    
    /**
    * Периодическое обновление кусов валют
    */
    public static function cron($params){
        $interval = \RS\Config\Loader::byModule('catalog')->cbr_auto_update_interval;
        if($interval){
            foreach($params['minutes'] as $minute){
                if ((($minute - 60) % $interval) == 0){
                    $api = new \Catalog\Model\CurrencyApi();
                    echo t("\n--- Обновление курсов валют: ");
                    echo ($api->getCBRFCourseWithUpdate()) ? t("успех"): t("неудача");
                    echo " ---\n";
                }
            } 
        }
    }
    
    /**
    * Регистрируем тип комментариев "комментарии к товару"
    * 
    * @param array $list - массив установленных ранее типов комментариев
    * @return array
    */
    public static function commentsGetTypes($list)
    {
        $list[] = new \Catalog\Model\CommentType\Product();
        return $list;
    }
}