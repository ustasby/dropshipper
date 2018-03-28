<?php
namespace Evasmart\Config;

use RS\Orm\Type\Decimal;
use \RS\Router;
use RS\Application\Application;
use RS\Application\Auth;
use RS\Config\Loader;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Helper\Tools;
use RS\Http\Request;
use RS\Orm\Type\Double;
use RS\Orm\Type\Enum;
use RS\Orm\Type\Integer;
use RS\Orm\Type\Varchar;
use RS\Router\Manager;
use Shop\Model\OrderApi;
use Shop\Model\Orm\CartItem;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\OrderItem;
use Users\Model\Orm\User;
use Catalog\Model\Orm\Product;
/**
* Класс содержит обработчики событий, на которые подписан модуль
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    /**
    * Добавляет подписку на события
    * 
    * @return void
    */
    function init()
    {
        $this
            ->bind('initialize')
            ->bind('controller.beforeexec.catalog-front-product')
            ->bind('controller.beforeexec.shop-front-cartpage')
            ->bind('controller.afterexec.shop-front-checkout')
            ->bind('controller.beforeexec.shop-front-checkout')
            ->bind('orm.beforewrite.shop-cart-item')
            ->bind('orm.beforewrite.shop-order-item') // для цен дропшипера
            ->bind('orm.beforewrite.shop-order')
            ->bind('controller.beforewrap') // js вставки
            ->bind('controller.exec.catalog-admin-ctrl.index')
            ->bind('orm.init.catalog-product')
            ->bind('orm.init.cart-item')
            ->bind('orm.init.shop-order')
            ->bind('orm.init.users-user')
            ->bind('getroute');  //событие сбора маршрутов модулей
            //->bind('getmenus'); //событие сбора пунктов меню для административной панели
    }

    public static function initialize()
    {
        // добавляем методы к корзине
        CartItem::attachClassBehavior(new \Evasmart\Model\Behavior\CartItem);
    }

    /**
     * Присваиваем к заказу персонального менеджера
     * @param $params
     */
    public static function ormBeforeWriteShopOrder($params)
    {
        $order = $params['orm'];
        $user = Auth::getCurrentUser();
        if ($user['manager_user_id']) {
            $order['manager_user_id'] = $user['manager_user_id'];
        } else {
            $managers_ids = array_keys(OrderApi::getUsersManagers());
            if ($managers_ids) {
                $order['manager_user_id'] = $managers_ids[rand(0, count($managers_ids) - 1)];
            }
        }

    }

    /**
     * Добавляем скрипты из конфига модуля
     *
     * @param $params
     * @return mixed
     */
    public static function controllerBeforewrap($params)
    {
        if (!Manager::obj()->isAdminZone()) {
            $config = Loader::byModule('evasmart');
            if ($config->head_scripts) {
                Application::getInstance()->setAnyHeadData(Tools::unEntityString($config->head_scripts));
            }

            if ($config->footer_scripts) {
                $params['body'] .= Tools::unEntityString($config->footer_scripts);
            }
            return $params;
        }
    }

    public static function getRoute(array $routes)
    {

        //Оформление заказа дропшипера
        $routes[] = new \RS\Router\Route('evasmart-front-checkout',
            '/ds/checkout/{Act}/', null, t('Оформление заказа'));
        // Установка цен дропшипера
        $routes[] = new \RS\Router\Route('evasmart-front-cartpage',
            '/ds/cart/{Act}/', null, t('Корзина'));
        return $routes;
    }
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * @param array $items - массив с пунктами меню
    * @return array
    */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => 'Пункт модуля Evasmart',
            'alias' => 'evasmart-control',
            'link' => '%ADMINPATH%/evasmart-control/',
            'parent' => 'modules',
            'sortn' => 40,
            'typelink' => 'link',
        );
        return $items;
    }

    /**
     * Добавим менеджера по умолчанию
     * @param User $user
     */
    public static function ormInitUsersUser(User $user)
    {

        $user->getPropertyIterator()->append([
            t('Основные'),
            'manager_user_id' => new Integer(array(
                'index' => true,
                'description' => t('Менеджер заказа'),
                'hint' => t('Менеджер по умолчанию'),
                'list' => array(array('\Shop\Model\OrderApi', 'getUsersManagersName'), array(0 => t('Не задан'))),
                'allowEmpty' => false
            )),

        ]);
    }

    public static function ormInitShopOrder(Order $order)
    {
        $order->getPropertyIterator()->append(array(
            'price_buyer' => new Decimal(array(
                'description' => t('Стоимость заказа для конечного покупателя')
            )),
            'price_delivery_buyer' => new Decimal(array(
                'description' => t('Стоимость доставки для конечного покупателя'))
            ),
            'prepay_buyer' => new Decimal(array(
                'description' => t('Предоплата от покупателя'))
            ),
            'ds_cost_val' => new Decimal(array(
                    'description' => t('Цена дропшипера'))
            ),
            'order_type' => new Integer(array(
                'description' => t('Тип заказа'))
            ),
        ));
    }

    public static function ormInitShopOrderItem(OrderItem $orderItem)
    {
        $orderItem->getPropertyIterator()->append(array(
            'ds_single_cost' => new Decimal(array(
                'description' => t('Цена дропшипера'))
            ),
            'ds_price' => new Decimal(array(
                'description' => t('Сумма дропшипера'))
            ),
        ));
    }

    /**
     * Добавляем цену дропшипера
     *
     * @param CartItem $cartItem
     */
    public static function ormInitShopCartItem(CartItem $cartItem)
    {
        $cartItem->getPropertyIterator()->append(array(
            t('Основные'),
            'ds_single_cost' => new Decimal(array(
                'description' => t('Цена дропшипера'))
            ),
            'ds_price' => new Decimal(array(
                'description' => t('Сумма дропшипера'))
            )
        ));
    }

    public static function ormInitCatalogProduct(Product $product)
    {
        $product->getPropertyIterator()->append(
            [
                t('Основные'),
                'type_product' => new Enum(
                    [
                        'default',
                        'mat'
                    ],
                    [
                        'visible'=> true,
                        'allowEmpty' => false,
                        'default' => 'default',
                        'description' => t('Тип товара'),
                        'ListFromArray' => [
                            [
                                'default' => t('По умолчанию'),
                                'mat' => t('Коврик')
                            ]
                        ],
                    ]
                ),
                'desc1' => new Varchar(
                    [
                        'maxLength' => '256',
                        'description' => t('Описание для оптовиков'),
                    ]
                ),
                'desc2' => new Varchar(
                    [
                        'maxLength' => '256',
                        'description' => t('Крепеж'),
                    ]
                ),
                'desc3' => new Varchar(
                    [
                        'maxLength' => '256',
                        'description' => t('Примечание'),
                    ]
                ),
                'processed' => new Integer(
                    [
                        'maxLength' => 1,
                        'default' => 0,
                        'visible' => false
                    ]
                )
            ]
        );
    }

    /**
     * Функция подвешивается на событие контролера - каталог товаров
     * @param CrudCollection $helper
     */
    public static function controllerExecCatalogAdminCtrlIndex(CrudCollection $helper)
    {

        $tool_bar   = $helper['topToolbar']->getItems();
        $request    = new Request();
        $dir        = $request->get('dir', TYPE_INTEGER, 0);  //Текущая категория товаров

        /**
         * @var \RS\Html\Toolbar\Button\Dropdown
         */
        $import_buttons = $tool_bar['import'];
        $import_buttons->addItem( //Добавляем кнопки импорта и экспорта
            array(
                'title' => t('Импорт каталога EvaSmart'),
                'attr' => array(
                    'href' => Manager::obj()->getAdminUrl(
                        'import',
                        array(
                            'referer'     => Manager::obj()->getAdminUrl(),
                        ),
                        'evasmart-importcsv'
                    ),
                    'class' => 'crud-add'
                ),

            ));
    }


    public static function controllerBeforeExecShopFrontCheckout(array $params)
    {
        //print_r($params['action']); exit;

        $action = $params['action'];
        $controller = $params['controller'];
        if ($action == 'confirm') {
            $request = Request::commonInstance();
            if ($request->isPost()) {
                $controller->order['price_buyer'] = $request->request('price_buyer', TYPE_FLOAT);
                $controller->order['order']['price_delivery_buyer'] = $request->request('price_delivery_buyer', TYPE_FLOAT);
                $controller->order['order']['prepay_buyer'] = $request->request('prepay_buyer', TYPE_FLOAT);
            } else {
                $cart_data = $controller->order['basket'] ? $controller->order->getCart()->getCartData() : null;
                $controller->order['price_buyer'] = 0;
                // выводим в конце заказа
                if (is_array($cart_data)) {
                    foreach ($cart_data['items'] as $item) {
                        $controller->order['price_buyer'] += $item['ds_single_cost'] * $item['amount'];
                    }
                }

                //print_r($cart_data); die();
            }
        }
    }

    public static function controllerAfterExecShopFrontCheckout($params)
    {
        //$view = $params['controller']->view;
        //print_r($params); exit;//$params
        //$vars = $params->getTemplateVars();
        //print_r($vars);
        //$action = $params['action'];
        //if ($action == 'payment') {}




    }

    /**
     * Распакуем материалы и цвета, добавим в шаблон
     *
     * @param array $params
     * @return array
     */
    public static function controllerBeforeExecCatalogFrontProduct(array $params)
    {
        //var_dump($params['controller']->view);
        $view = $params['controller']->view;
        $config = Loader::byModule('evasmart');
        $view->assign('color_mat', ['color_sota' => 'Сота', 'color_romb' => 'Ромб']);

        $view->assign('color_romb', array_map(function ($v) {
            return trim($v);
        }, explode("\r\n", $config['color_romb'])));

        $view->assign('color_sota', array_map(function ($v) {
            return trim($v);
        }, explode("\r\n", $config['color_sota'])));

        $view->assign('color_kant', array_map(function ($v) {
            return trim($v);
        }, explode("\r\n", $config['color_kant'])));

        return $params;
    }

    /**
     * Добавим уникальный ключ товара при добавлении в корзину
     * @param array $params
     * @return array
     */
    public static function controllerBeforeExecShopFrontCartPage(array $params)
    {
        $action = $params['action'];
        // \Shop\Controller\Front\CartPage::actionIndex
        if ($action == 'index') {
            $request = Request::commonInstance();
            if ($request->request('color_mat', TYPE_STRING)) {
                //$uniq = md5($request->request('color_mat', TYPE_STRING) . $request->request('color', TYPE_STRING) . $request->request('kant', TYPE_STRING));
                $uniq = 'Материал: ' . $request->request('color_mat', TYPE_STRING) . ', Цвет: ' . $request->request('color', TYPE_STRING) . ', Кант: ' . $request->request('kant', TYPE_STRING);
                $request->set('uniq', $uniq, GET);
            }
        }
        //var_dump($params); exit;
        return $params;
    }

}