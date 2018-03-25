<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Config;
use \RS\Router;
use \RS\Orm\Type as OrmType;
use \RS\Html\Table\Type as TableType;
use \RS\Html\Filter;
use Shop\Model\OrderApi;
use Shop\Model\ProductsReturnApi;
use Shop\Model\ReservationApi;

class Handlers extends \RS\Event\HandlerAbstract
{
    /**
     * Инициализация модуля
     */
    function init()
    {
        $this
            ->bind('getroute')
            ->bind('getmenus')
            ->bind('user.auth')
            ->bind('delivery.gettypes')
            ->bind('payment.gettypes')
            ->bind('printform.getlist')
            ->bind('orm.init.catalog-product')
            ->bind('orm.init.catalog-dir')
            ->bind('orm.init.users-user')
            ->bind('controller.exec.users-admin-ctrl.index')
            ->bind('api.oauth.token.success')
            ->bind('meter.recalculate')
            ->bind('cron');
            
        if (\Setup::$INSTALLED) {
            $this->bind('orm.afterwrite.site-site', $this, 'onSiteCreate');
        }
    }

    /**
     * Добавляем информацию о количестве непросмотренных заказов
     * во время вызова события пересчета счетчиков
     *
     * @param array $meters - параметры метрики
     * @return mixed
     */
    public static function meterRecalculate($meters)
    {
        $order_api = new OrderApi();
        $order_meter_api = $order_api->getMeterApi();
        $meters[$order_meter_api->getMeterId()] = $order_meter_api->getUnviewedCounter();

        $reservation_api = new ReservationApi();
        $reservation_meter_api = $reservation_api->getMeterApi();
        $meters[$reservation_meter_api->getMeterId()] = $reservation_meter_api->getUnviewedCounter();

        $return_api = new ProductsReturnApi();
        $return_meter_api = $return_api->getMeterApi();
        $meters[$return_meter_api->getMeterId()] = $return_meter_api->getUnviewedCounter();

        return $meters;
    }

    /**
     * Добавляем к сведениям об авторизации, сведения о том, является ли пользователь курьером
     * @param array $params - параметры удачной авторизации
     * @return array
     * @throws \RS\Exception
     */
    public static function apiOauthTokenSuccess($params)
    {
        $user_id = $params['result']['response']['user']['id'];
        
        $courier_user_group = \RS\Config\Loader::byModule(__CLASS__)->courier_user_group;
        $user = new \Users\Model\Orm\User($user_id);
        $params['result']['response']['user']['is_courier'] = in_array($courier_user_group, $user->getUserGroups());
        
        return $params;
    }  
    
    
    /**
    * Расширяет функционал контроллера админ панели пользователей
    * 
    * @param \RS\Controller\Admin\Helper\CrudCollection $crud_collection - объект контроллера
    */
    public static function controllerExecUsersAdminCtrlIndex(\RS\Controller\Admin\Helper\CrudCollection $crud_collection)
    {
        //Добавим колонку "Баланс" в таблицу с пользователями
        /**
        * @var \RS\Html\Table\Control $table
        */
        $table = $crud_collection['table'];
        $table->getTable()->addColumn(new TableType\Text('balance', t('Баланс'), array('Sortable' => SORTABLE_BOTH)), -1);
        
        //Добавим фильтр по балансу
        /**
        * @var \RS\Html\Filter\Control $filter
        */
        $filter = $crud_collection['filter'];
        
        $container = $filter->getContainer();
        $lines = $container->getLines();
        $lines[0]->addItem(new Filter\Type\Text('balance', t('Баланс'), array(
            'showType' => true
        )));
        $container->cleanItemsCache();
        
        //Добавим действия в таблицу
        $router = \RS\Router\Manager::obj();
        $columns = $table->getTable()->getColumns();
        foreach($columns as $column) {
            if ($column instanceof TableType\Actions) {
                foreach($column->getActions() as $action) {
                    if ($action instanceof TableType\Action\DropDown) {
                        $action->addItem(array(
                                'title' => t('история транзакций'),
                                'attr' => array(
                                    '@href' => $router->getAdminPattern(false, array(':f[user_id]' => '@id'), 'shop-transactionctrl'),
                                )
                        ));
                        
                        $action->addItem(array(
                                'title' => t('исправить баланс'),
                                'attr' => array(
                                    'class' => 'crud-get',
                                    '@href' => $router->getAdminPattern('fixBalance', array(':id' => '@id'), 'shop-balancectrl'),
                                )
                        ));
                    }
                }
                $column->addAction(new TableType\Action\Action($router->getAdminPattern('addfunds', array(':id' => '~field~', 'writeoff' => 0), 'shop-balancectrl'), t('пополнить баланс'), array('iconClass' => 'money', 'class' => 'crud-add')), 0);
                $column->addAction(new TableType\Action\Action($router->getAdminPattern('addfunds', array(':id' => '~field~', 'writeoff' => 1), 'shop-balancectrl'), t('списать с баланса'), array('iconClass' => 'money-off', 'class' => 'crud-add')), 0);
            }
        }
    }

    /**
     * Возвращает маршруты данного модуля
     *
     * @param array $routes - ранее установленые маршруты
     * @return array
     */
    public static function getRoute(array $routes) 
    {        
        //Корзина
        $routes[] = new Router\Route('shop-front-cartpage', '/cart/', null, t('Корзина'));
        //Выбор многомерной комплектации
        $routes[] = new \RS\Router\Route('shop-front-multioffers', 
            '/multioffers/{product_id}/', null, t('Выбор многомерной комплектации'));
        //Оформление заказа
        $routes[] = new Router\Route('shop-front-checkout', array('/checkout/{Act}/', '/checkout/'), null, t('Оформление заказа'));
        //Документ на оплату
        $routes[] = new Router\Route('shop-front-documents', '/paydocuments/', null, t('Документ на оплату'));
        //Лицензионное соглашение
        $routes[] = new Router\Route('shop-front-licenseagreement', '/license-agreement/', null, t('Лицензионное соглашение'));
        //Просмотр заказа
        $routes[] = new Router\Route('shop-front-myorderview', array('/my/orders/view-{order_id}/'), null, t('Просмотр заказа'));
        //Мои заказы
        $routes[] = new Router\Route('shop-front-myorders', array('/my/orders/'), null, t('Мои заказы'));
        //Мои возвраты
        $routes[] = new Router\Route('shop-front-myproductsreturn', array('/my/productsreturn/{Act:(add|edit|delete|print|view|rules)}/', '/my/productsreturn/'), null, t('Мои возвраты'));
        //Лицевой счет
        $routes[] = new Router\Route('shop-front-mybalance', array('/my/balance/{Act}/', '/my/balance/'), null, t('Лицевой счет'));
        //Online платежи
        $routes[] = new Router\Route('shop-front-onlinepay', array('/onlinepay/{PaymentType}/{Act:(success|fail|result)}/', '/onlinepay/{Act}/'), null, t('Online платежи'));
        //Список регионов
        $routes[] = new Router\Route('shop-front-regiontools', '/regiontools/', null, t('Список регионов'), true); 
        //Предварительный заказ товара
        $routes[] = new Router\Route('shop-front-reservation', '/reservation/{product_id}/', null, t('Предварительный заказ товара'));
        //Контроллер для приёма команд от касс онлайн
        $routes[] = new \RS\Router\Route('shop-front-cashregister', array(
            '/cashregister/{CashRegisterType}/{Act}/',
            '/cashregister/{CashRegisterType}/'
        ), null, t('Шлюз обмена данными с кассами'), true);
        
        return $routes;
    }

    /**
     * Привязывает корзину к пользователю после авторизации
     *
     * @param array $params - массив параметров с объектами пользователя
     * @throws \RS\Db\Exception
     */
    public static function userAuth($params)
    {
        /**
         * @var \Users\Model\Orm\User $user
         */
        $user = $params['user'];
        $guest_id = \RS\Http\Request::commonInstance()->cookie('guest', TYPE_STRING, false);
        //Привязываем корзину к пользователю
        $cart = \Shop\Model\Cart::currentCart();
        $items = $cart->getCartItemsByType();
        if (count($items)) {
            //Если будучи неавторизованным, пользователь собрал новую корзину, 
            //то НЕ импортируем корзину от авторизованного пользователя
            \RS\Orm\Request::make()->delete()
                ->from(new \Shop\Model\Orm\CartItem())
                ->where("user_id = '#user_id' AND session_id != '#session_id'", array(
                    'session_id' => $guest_id,
                    'user_id' => $user['id']
                ))
                ->exec();
        } else {
            //Если текущая корзина пользователя пуста, а у авторизованного пользователя была собрана, 
            //то импортируем её
            \RS\Orm\Request::make()->update(new \Shop\Model\Orm\CartItem())
            ->set(array(
                'session_id' => $guest_id
            ))->where(array(
                'user_id' => $user['id']
            ))->exec();
            
            $cart->cleanInfoCache();
        }
        
        \RS\Orm\Request::make()->update(new \Shop\Model\Orm\CartItem())
        ->set(array(
            'user_id' => $user['id']
        ))->where(array(
            'session_id' => $guest_id
        ))->exec();
        
        \Shop\Model\Cart::destroy();
    }
    
    /**
    * Возвращает процессоры(типы) доставки, присутствующие в текущем модуле
    * 
    * @param array $list - массив из передаваемых классов доставок
    * @return array
    */
    public static function deliveryGetTypes($list)
    {        
        $list[] = new \Shop\Model\DeliveryType\FixedPay();
        $list[] = new \Shop\Model\DeliveryType\Myself();
        $list[] = new \Shop\Model\DeliveryType\Manual();
        $list[] = new \Shop\Model\DeliveryType\Ems();
        $list[] = new \Shop\Model\DeliveryType\Spsr();
        $list[] = new \Shop\Model\DeliveryType\RussianPost();
        $list[] = new \Shop\Model\DeliveryType\Universal();
        $list[] = new \Shop\Model\DeliveryType\Sheepla();
        $list[] = new \Shop\Model\DeliveryType\Cdek();
        $list[] = new \Shop\Model\DeliveryType\RussianPostCalc();
        return $list;
    }
    
    /**
    * Возвращает способы оплаты, присутствующие в текущем модуле
    * 
    * @param array $list - массив из передаваемых классов оплат
    * @return array
    */
    public static function paymentGetTypes($list)
    {
        $list[] = new \Shop\Model\PaymentType\Cash();
        $list[] = new \Shop\Model\PaymentType\Bill();
        $list[] = new \Shop\Model\PaymentType\FormPd4();
        $list[] = new \Shop\Model\PaymentType\Robokassa();
        $list[] = new \Shop\Model\PaymentType\Assist();
        $list[] = new \Shop\Model\PaymentType\PayPal();
        $list[] = new \Shop\Model\PaymentType\YandexMoney();
        $list[] = new \Shop\Model\PaymentType\PersonalAccount();
        $list[] = new \Shop\Model\PaymentType\Toucan();
        return $list;
    }

    /**
     * Обрабатывает событие - создание сайта
     *
     * @param array $params - массив параметров с объектом сайта
     */
    public static function onSiteCreate($params)
    {
        if ($params['flag'] == \RS\Orm\AbstractObject::INSERT_FLAG) {

            $site = $params['orm'];
            \Shop\Model\Orm\UserStatus::insertDefaultStatuses($site['id']); //Добавляем статусы заказов по-умолчанию

            $module = new \RS\Module\Item('shop');
            /**
             * @var \Shop\Config\Install $installer
             */
            $installer = $module->getInstallInstance();
            $installer->importCsv(new \Shop\Model\CsvSchema\Region(), 'regions', $site['id']);
            $installer->importCsv(new \Shop\Model\CsvSchema\Zone(), 'zones', $site['id']);
            $installer->importCsv(new \Shop\Model\CsvSchema\SubStatus(), 'substatus', $site['id']);
        }
    }    
    
    /**
    * Добавляем раздел "Налоги" в карточку товара
    */
    public static function ormInitCatalogProduct(\Catalog\Model\Orm\Product $orm_product)
    {
        $orm_product->getPropertyIterator()->append(array(
            t('Налоги'),
            'tax_ids' => new OrmType\Varchar(array(
                'description' => t('Налоги'),
                'template' => '%shop%/productform/taxes.tpl',
                'default' => 'category',
                'list' => array(array('\Shop\Model\TaxApi', 'staticSelectList'))
            ))
        ));
    }

    /**
    * Добавляем раздел "Налоги" в категорию товара
    */
    public static function ormInitCatalogDir(\Catalog\Model\Orm\Dir $orm_dir)
    {
        $orm_dir->getPropertyIterator()->append(array(
            t('Налоги'),
            'tax_ids' => new OrmType\Varchar(array(
                'description' => t('Налоги'),
                'default' => 'all',
                'template' => '%shop%/productform/taxes_dir.tpl',
                'list' => array(array('\Shop\Model\TaxApi', 'staticSelectList')),
                'rootVisible' => false
            ))
        ));
    }
    
    /**
    * Расширяем объект User, добавляя в него доп свойство "Менеджер пользователя"
    * 
    * @param \Users\Model\Orm\User $user
    */
    public static function ormInitUsersUser(\Users\Model\Orm\User $user)
    {
        $user->getPropertyIterator()->append(array(
            t('Основные'),
                'manager_user_id' => new OrmType\Integer(array(
                    'index' => true,
                    'description' => t('Менеджер пользователя'),
                    'hint' => t('У всех заказов пользователя будет автоматически указываться выбранный менеджер'),
                    'list' => array(array('\Shop\Model\OrderApi', 'getUsersManagersName'), array(0 => t('- Не задан -'))),
                    'allowEmpty' => false,
                )),
        ));
    }
    
    /**
    * Добавляет в систему печатные формы для заказа
    *
    * @param array $list - массив установленных меню
    * @return array
    */
    public static function printFormGetList($list)
    {
        $list[] = new \Shop\Model\PrintForm\OrderForm();
        $list[] = new \Shop\Model\PrintForm\CommodityCheck();
        $list[] = new \Shop\Model\PrintForm\DeliveryNote();
        return $list;
    }
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    *
    * @param array $items - массив установленных меню
    * @return array
    */
    public static function getMenus($items){

        $items[] = array(
                'title' => t('Магазин'),
                'alias' => 'orders',
                'link' => '%ADMINPATH%/shop-orderctrl/',
                'parent' => 0,
                'sortn' => 10,
                'typelink' => 'link',                        
            );
        $items[] = array(
                'title' => t('Заказы'),
                'alias' => 'allorders',
                'link' => '%ADMINPATH%/shop-orderctrl/',
                'sortn' => 0,
                'typelink' => 'link',                       
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Предварительные заказы'),
                'alias' => 'advorders',
                'link' => '%ADMINPATH%/shop-reservationctrl/',
                'sortn' => 1,
                'typelink' => 'link',                       
                'parent' => 'orders'
            );
        $items[] = array(
                'typelink' => 'separator',
                'alias' => 'afteradvorders',
                'sortn' => 2,                  
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Скидочные купоны'),
                'alias' => 'discount',
                'link' => '%ADMINPATH%/shop-discountctrl/',
                'sortn' => 3,
                'typelink' => 'link',                       
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Доставка'),
                'alias' => 'deliverygroup',
                'link' => '%ADMINPATH%/shop-regionctrl/',
                'sortn' => 4,
                'typelink' => 'link',                        
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Способы доставки'),
                'alias' => 'delivery',
                'link' => '%ADMINPATH%/shop-deliveryctrl/',
                'parent' => 'deliverygroup',
                'typelink' => 'link',  
                'sortn' => 1,
                                   
            );            
        $items[] = array(
                'title' => t('Регионы доставки'),
                'alias' => 'regions',
                'link' => '%ADMINPATH%/shop-regionctrl/',
                'parent' => 'deliverygroup',
                'typelink' => 'link',      
                'sortn' => 2,
                                
            );
        $items[] = array(
                'title' => t('Зоны'),
                'alias' => 'zones',
                'link' => '%ADMINPATH%/shop-zonectrl/',
                'parent' => 'deliverygroup',
                'sortn' => 3,
                'typelink' => 'link',
            );
        $items[] = array(
                'title' => t('Способы оплаты'),
                'alias' => 'payment',
                'link' => '%ADMINPATH%/shop-paymentctrl/',
                'sortn' => 5,
                'typelink' => 'link',
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Налоги'),
                'alias' => 'taxes',
                'link' => '%ADMINPATH%/shop-taxctrl/',
                'sortn' => 7,
                'typelink' => 'link',
                'parent' => 'orders'
            );
        $items[] = array(
                'title' => t('Транзакции'),
                'alias' => 'transactions',
                'parent'=> 'userscontrol',
                'link' => '%ADMINPATH%/shop-transactionctrl/',
                'sortn' => 40,
                'typelink' => 'link',                     
            );
        $items[] = array(
            'title' => t('Электронные чеки'),
            'alias' => 'receipt',
            'parent'=> 'userscontrol',
            'link' => '%ADMINPATH%/shop-receiptsctrl/',
            'sortn' => 42,
            'typelink' => 'link',
        );
        $items[] = array(
            'title' => t('Возвраты товаров'),
            'alias' => 'returns',
            'parent'=> 'orders',
            'link' => '%ADMINPATH%/shop-returnsctrl/',
            'sortn' => 9,
            'typelink' => 'link',
        );
        return $items;
    }

    /**
     * Обработка событий по cron
     *
     * @param array $params - параметры cron
     *
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    public static function cron($params)
    {
        //Запускаем в полночь проверку на автоматический перевод статусов заказов,
        //которые находятся в статусе L более N дней
        if (in_array(0, $params['minutes']))
        {
            $sites = \RS\Site\Manager::getSiteList();
            foreach($sites as $site) {
                $config = \RS\Config\Loader::byModule(__CLASS__, $site['id']);
                if ($config['auto_change_status'] 
                    && $config['auto_change_timeout_days'] 
                    && $config['auto_change_from_status']) 
                {
                    self::autoChangeOrderStatus($config, $site['id']);
                }
            }
            
        }
        // Запускаем отправку уведомлений подписавшимся клиентам о поступлении товара 
        foreach($params['minutes'] as $minute) {
            // Задание запускается в 09:00, 12:00, 16:00
            if (in_array($minute, array(540, 720, 960))) 
            { 
                $sites = \RS\Site\Manager::getSiteList();
                foreach($sites as $site) {
                    $config = \RS\Config\Loader::byModule(__CLASS__, $site['id']);
                    if ($config['auto_send_supply_notice']) {                        
                        \RS\Site\Manager::setCurrentSite($site);
                        $count = \Shop\Model\ReservationApi::SendNoticeReceipt($site['id']);        
                    }
                }
            }
        }


        //Проверим чеки раз в минуту, на случай если callback не отработал или него не существует впринципе.
        $sites = \RS\Site\Manager::getSiteList();
        foreach($sites as $site) {
            $config = \RS\Config\Loader::byModule(__CLASS__, $site['id']);
            if ($config['cashregister_class'] && $config['cashregister_enable_auto_check']){ //Если только класс для касс задан и стоит флаг для проверки
                $api = new \Shop\Model\ReceiptApi();
                $api->checkWaitReceipts($site['id']);
            }
        }
    }

    /**
     * Автоматически переводит статус заказов, согласно настройкам модуля
     *
     * @param \Shop\Config\File $config - объект конфига
     * @param integer $site_id - id сайта
     * @throws \RS\Orm\Exception
     */
    private static function autoChangeOrderStatus($config, $site_id)
    {
        $to_status = $config['auto_change_to_status'];
        $limit = 40;
        $offset = 0;

        $q = \RS\Orm\Request::make()
            ->from(new \Shop\Model\Orm\Order())
            ->where('dateofupdate < NOW() - INTERVAL #n DAY', array(
                'n' => $config['auto_change_timeout_days']
            ))
            ->whereIn('status', $config['auto_change_from_status'])
            ->where(array(
                'site_id' => $site_id
            ))
            ->limit($limit);
        
        while($orders = $q->offset($offset)->objects()) {
            foreach($orders as $order) {
                
                echo t('Автоматически меняем статус заказа ID:%0', array($order['id']))."\n";
                
                $order['status'] = $to_status;
                $order['is_exported'] = 0;
                $order->update();
                
                //Отправляем уведомление при автосмене статуса
                $notice = new \Shop\Model\Notice\AutoChangeStatus();
                $notice->init($order);
                \Alerts\Model\Manager::send($notice);
                
            }
            $offset += $limit;
        }   
    }
}