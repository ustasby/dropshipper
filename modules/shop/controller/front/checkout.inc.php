<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;
use Main\Model\StatisticEvents;
use \RS\Application\Auth as AppAuth;

/**
* Контроллер Оформление заказа
*/
class Checkout extends \RS\Controller\Front
{
    /**
     * @var \Shop\Model\OrderApi $order_api
     */
    public $order_api;
    /**
    * @var \Shop\Model\Orm\Order $order
    */
    public $order;
        
    /**
    * Инициализация контроллера
    */
    function init()
    {
        $this->app->title->addSection(t('Оформление заказа'));
        $this->order = \Shop\Model\Orm\Order::currentOrder();
        $this->order_api = new \Shop\Model\OrderApi();
        
        $this->order->clearErrors();
        $this->view->assign('order', $this->order);
    }
    
    
    function actionIndex()
    {
        $this->order->clear();
                 
        //Замораживаем объект "корзина" и привязываем его к заказу
        $frozen_cart = \Shop\Model\Cart::preOrderCart(null);
        $frozen_cart->splitSubProducts();
        $frozen_cart->mergeEqual();
        
        $this->order->linkSessionCart($frozen_cart);
        $this->order->setCurrency( \Catalog\Model\CurrencyApi::getCurrentCurrency() );
        
        $this->order['ip'] = $_SERVER['REMOTE_ADDR'];
        $this->order['warehouse'] = 0;
        
        $this->order['expired'] = false;
        $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'address')));
    }
    
    /**
    * Шаг 1. Установка адреса и контактов
    */
    function actionAddress()
    {
        if ( !$this->order->getCart() ) $this->redirect();
        $this->app->title->addSection(t('Адрес и контакты'));
        $config = \RS\Config\Loader::byModule($this);
        //Добавим хлебные крошки
        $this->app->breadcrumbs
                ->addBreadCrumb(t('Корзина'), $this->router->getUrl('shop-front-cartpage')) 
                ->addBreadCrumb(t('Адрес и контакты'));
        
        $logout = $this->url->request('logout', TYPE_BOOLEAN);
        $login  = $this->url->request('ologin', TYPE_BOOLEAN); //Предварительная авторизация
        
        
        if ($logout) {
            AppAuth::logout();
            $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'address')));
        }
        ;
        if (AppAuth::isAuthorize()) {
            $this->order['user_type'] = null;
        } else {
            $this->order['__code']->setEnable(true);
            
            if (empty($this->order['user_type'])) {
                $this->order['user_type'] = 'person';
                $this->order['reg_autologin'] = 1;
            }
        }
        
        $cart_data = $this->order['basket'] ? $this->order->getCart()->getCartData() : null;
        if ($cart_data === null || !count($cart_data['items']) || $cart_data['has_error'] || $this->order['expired']) {
            //Если корзина пуста или заказ уже оформлен или имеются ошибки в корзине, то выполняем redirect на главную сайта
            $this->redirect();
        }
        
        
        //Запрашиваем дополнительные поля формы заказа, если они определены в конфиге модуля
        $order_fields_manager  = $this->order->getFieldsManager();
        $order_fields_manager->setValues($this->order['userfields_arr']);
        
        //Запрашиваем дополнительные поля формы регистрации, если они определены
        $reg_fields_manager = \RS\Config\Loader::byModule('users')->getUserFieldsManager();
        $reg_fields_manager->setErrorPrefix('regfield_');
        $reg_fields_manager->setArrayWrapper('regfields');
        if (!empty($this->order['regfields'])){
            $reg_fields_manager->setValues($this->order['regfields']);    
        }
               
        if ($this->url->isPost()) { //POST
            $this->order['only_pickup_points'] = $this->request('only_pickup_points', TYPE_INTEGER, 0); //Флаг использования только самовывоза
            $this->order_api->addOrderExtraDataByStep($this->order, 'address', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные
            $sysdata = array('step' => 'address');
            $work_fields = $this->order->useFields( $sysdata + $_POST );
            
            if ($this->order['only_pickup_points']){ //Если только самовывоз то исключим поля
                $work_fields = array_diff($work_fields, array('addr_country_id', 'addr_region', 'addr_region_id', 'addr_city', 'addr_zipcode', 'addr_address', 'use_addr'));
                $this->order['use_addr'] = 0;
            }
             
            $this->order->setCheckFields($work_fields);
            $this->order->checkData($sysdata, null, null, $work_fields);
            $this->order['userfields'] = serialize($this->order['userfields_arr']);
               
            //Авторизовываемся
            if ($this->order['user_type'] == 'user' && !$logout) {    
                if (!\RS\Application\Auth::login($this->order['login'], $this->order['password'])) {
                    $this->order->addError(t('Неверный логин или пароль'), 'login');
                } else {
                    $this->order['user_type'] = '';
                    $this->order['__code']->setEnable(false);
                }
            }

            
            if (!$logout && !$login) {
                       
                //Проверяем пароль, если пользователь решил задать его вручную. (при регистрации)
                if (in_array($this->order['user_type'], array('person', 'company')) && !$this->order['reg_autologin']) {
                    if (($pass_err = \Users\Model\Orm\User::checkPassword($this->order['reg_openpass'])) !== true) {
                        $this->order->addError($pass_err, 'reg_openpass');
                    } 
                    
                    
                    if(strcmp($this->order['reg_openpass'], $this->order['reg_pass2'])){
                        $this->order->addError(t('Пароли не совпадают'), 'reg_openpass');  
                    } 

                
                    //Сохраняем дополнительные сведения о пользователе
                    $uf_err = $reg_fields_manager->check($this->order['regfields']);
                    if (!$uf_err) {
                        //Переносим ошибки в объект order
                        foreach($reg_fields_manager->getErrors() as $form=>$errortext) {
                            $this->order->addError($errortext, $form);
                        }
                    }                    
                }                    
                
                //Регистрируем пользователя, если нет ошибок            
                if (in_array($this->order['user_type'], array('person', 'company'))) {
                    
                    $new_user = new \Users\Model\Orm\User();
                    $allow_fields = array('reg_name', 'reg_surname', 'reg_midname', 'reg_phone', 'reg_e_mail', 
                                            'reg_openpass', 'reg_company', 'reg_company_inn');
                    $reg_fields = array_intersect_key($this->order->getValues(), array_flip($allow_fields));
                    
                    $new_user->getFromArray($reg_fields, 'reg_');
                    $new_user['data'] = $this->order['regfields'];
                    $new_user['is_company'] = (int)($this->order['user_type'] == 'company');
                                        
                    if (!$new_user->validate()) {
                        foreach($new_user->getErrorsByForm() as $form => $errors) {
                            $this->order->addErrors($errors, 'reg_'.$form);
                        }
                    }
                    
                    if (!$this->order->hasError()) {
                        if ($this->order['reg_autologin']) {
                            $new_user['openpass'] = \RS\Helper\Tools::generatePassword(6);
                        }
                        
                        if ($new_user->create()) {
                            if (AppAuth::login($new_user['login'], $new_user['pass'], true, true)) {
                                $this->order['user_type'] = ''; //Тип регитрации - не актуален после авторизации
                                $this->order['__code']->setEnable(false);                        
                            } else {
                                throw new \RS\Exception(t('Не удалось авторизоваться под созданным пользователем.'));                        
                            }
                        } else {
                            $this->order->addErrors($new_user->getErrorsByForm('e_mail'), 'reg_e_mail');
                            $this->order->addErrors($new_user->getErrorsByForm('login'), 'reg_login');
                        }
                    }
                }
                
                //Если заказ без регистрации пользователя
                if ($this->order['user_type'] == 'noregister') {
                   //Получим данные 
                   $this->order['user_fio']   = $this->request('user_fio', TYPE_STRING); 
                   $this->order['user_email'] = $this->request('user_email', TYPE_STRING); 
                   $this->order['user_phone'] = $this->request('user_phone', TYPE_STRING); 
                   
                   //Проверим данные
                   if (empty($this->order['user_fio'])){
                       $this->order->addError(t('Укажите, пожалуйста, Ф.И.О.'), 'user_fio');
                   }
                   if ($this->getModuleConfig()->require_email_in_noregister && !filter_var($this->order['user_email'], FILTER_VALIDATE_EMAIL)){
                       $this->order->addError(t('Укажите, пожалуйста, E-mail'), 'user_email');
                   }
                   
                   if ($this->getModuleConfig()->require_phone_in_noregister && empty($this->order['user_phone'])){
                       $this->order->addError(t('Укажите, пожалуйста, Телефон'), 'user_phone');
                   }
                }
                
                //Сохраняем дополнительные сведения
                $uf_err = $order_fields_manager->check($this->order['userfields_arr']);
                if (!$uf_err) {
                    //Переносим ошибки в объект order
                    foreach($order_fields_manager->getErrors() as $form=>$errortext) {
                        $this->order->addError($errortext, $form);
                    }
                }
                
                //Сохраняем адрес
                if (!$this->order->hasError() && $this->order['use_addr'] == 0 && !$this->order['only_pickup_points']) {
                    $address = new \Shop\Model\Orm\Address();
                    $address->getFromArray($this->order->getValues(), 'addr_');
                    $address['user_id'] = AppAuth::getCurrentUser()->id;                
                    if ($address->insert()) {
                        $this->order['use_addr'] = $address['id'];
                    }
                }
                
                
                //Все успешно
                if (!$this->order->hasError()) {

                    // Фиксация события "Указание адреса" для статистики
                    \RS\Event\Manager::fire('statistic', array('type' => StatisticEvents::TYPE_SALES_FILL_ADDRESS));

                    $this->order['user_id'] = AppAuth::getCurrentUser()->id;
                    $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'delivery'))); 
                }
            } //!logout && !login
            
            
        }else{
            //Установим адрес по умолчанию
            $this->order->setDefaultAddress();
        } 
        
        
        $user = AppAuth::getCurrentUser();
        if (AppAuth::isAuthorize()) {
            //Получаем список адресов пользователя
            $address_api = new \Shop\Model\AddressApi();
            $address_api->setFilter('user_id', $user['id']);
            $address_api->setFilter('deleted', 0);
            $addr_list = $address_api->getList();
            if (count($addr_list)>0 && $this->order['use_addr'] === null) {
                $this->order['use_addr'] = $addr_list[0]['id'];
            }
            $this->view->assign('address_list', $addr_list);
        }
        
        if ($logout) {
            $this->order->clearErrors();
        }
        
        if ($login) { //Покажем только ошибки авторизации, остальные скроем
            $login_err = $this->order->getErrorsByForm('login');
            $this->order->clearErrors();
            if (!empty($login_err)) $this->order->addErrors($login_err, 'login');
        }

        //Посмотрим есть ли варианты для доставки по адресу и для самовывоза
        $have_to_address_delivery = \Shop\Model\DeliveryApi::isHaveToAddressDelivery($this->order);
        $have_pickup_points = \Shop\Model\DeliveryApi::isHavePickUpPoints($this->order);
        $this->view->assign(array(
            'have_to_address_delivery' => $have_to_address_delivery,
            'have_pickup_points' => $have_pickup_points,
        ));

        if (!$this->url->isPost()) {
            if ($have_pickup_points && ($config['myself_delivery_is_default'] || !$have_to_address_delivery)) {
                $this->order['only_pickup_points'] = true;
            } else {
                $this->order['only_pickup_points'] = false;
            }
        }
        
        $this->order['password']     = '';
        $this->order['reg_openpass'] = '';
        $this->order['reg_pass2']    = '';
        
        $this->view->assign(array(
            'is_auth'         => AppAuth::isAuthorize(),
            'order'           => $this->order,
            'order_extra'     => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
            'user'            => $user,
            'conf_userfields' => $order_fields_manager,
            'reg_userfields'  => $reg_fields_manager,
        ));
        return $this->result->setTemplate( 'checkout/address.tpl' );
    }
    
    /**
    * Шаг 2. Выбор доставки
    */
    function actionDelivery()
    {
        $delivery_api = new \Shop\Model\DeliveryApi();
        $delivery_list = $delivery_api->getCheckoutDeliveryList($this->user, $this->order);

        // Если доставка ещё не выбрана - выбираем первую доставку по умолчанию
        if (empty($this->order['delivery'])) {
            foreach ($delivery_list as $delivery) {

                if ($delivery['default']) {
                    $this->order['delivery'] = $delivery['id'];
                    break;
                }
            }
        }

        $statistic_event = array('type' => StatisticEvents::TYPE_SALES_SELECT_DELIVERY);

        if ($this->getModuleConfig()->hide_delivery) { //Если нужно проскочить шаг доставка

            // Фиксация события "Выбран способ доставки" для статистики
            \RS\Event\Manager::fire('statistic', $statistic_event);
            $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'payment'))); 
        }
        
        $this->app->title->addSection(t('Выбор доставки'));
        
        //Добавим хлебные крошки
        $this->app->breadcrumbs
                    ->addBreadCrumb(t('Корзина'),$this->router->getUrl('shop-front-cartpage')) 
                    ->addBreadCrumb(t('Адрес и контакты'), $this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'address'
                    ))) 
                    ->addBreadCrumb(t('Выбор доставки'));

        if ( $this->order['expired'] || !$this->order->getCart() ) $this->redirect();
        
        //Если есть доставка, и она одна, и выбран только самовывоз, то перейдём на склады
        if(!empty($delivery_list) && count($delivery_list)==1 && $this->order['only_pickup_points']){ //Если доставка всего одна и выбран только самовывоз


            // Фиксация события "Выбран способ доставки" для статистики
            \RS\Event\Manager::fire('statistic', $statistic_event);
            $orderdelivery = reset($delivery_list) ;
            $this->order['delivery'] = $orderdelivery['id'];
            $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'warehouses')));
        }
        
        $this->view->assign(array(
            'delivery_list' => $delivery_list 
        ));
        
        
        if ($this->url->isPost()) {
            $this->order_api->addOrderExtraDataByStep($this->order, 'delivery', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные  
            
            //Проверим параметры выбора доставки
            $sysdata = array('step' => 'delivery');
            $work_fields = $this->order->useFields($sysdata + $this->url->getSource(POST));
            $this->order->setCheckFields($work_fields);
            if ($this->order->checkData($sysdata, null, null, $work_fields)) {

                // Фиксация события "Выбран способ доставки" для статистики
                \RS\Event\Manager::fire('statistic', $statistic_event);

                $delivery       = $this->order->getDelivery(); //Выбранная доставка
                $delivery_extra = $this->request('delivery_extra',TYPE_ARRAY,false);
                if ($delivery_extra){
                    $this->order->addExtraKeyPair('delivery_extra', $delivery_extra);
                }
                
                if ($delivery['class'] == 'myself'){ //Если самовывоз и складов больше одного
                   $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'warehouses'))); 
                }else{                   
                   $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'payment')));
                }
            }
        }
        
        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));
         
        return $this->result->setTemplate( 'checkout/delivery.tpl' );        
    }
    
    /**
    * Шаг 2.2 Страница выбора склада откуда забирать
    * Используется только когда складов более одного 
    * и выбран способ доставки "Самовывоз"
    * 
    */
    function actionWarehouses()
    {
        $this->app->title->addSection(t('Выбор склада для забора товара'));
        
        $warehouses = \Catalog\Model\WareHouseApi::getPickupWarehousesPoints(); //Получим пункты самовывоза
        
        if (count($warehouses) < 2){
            if (count($warehouses) == 1) {
                //Если склад только один, то пропускаем выбор склада
                $this->order['warehouse'] = $warehouses[0]['id'];
            }
            $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'payment')));
        }                   
        
        //Добавим хлебные крошки
        $this->app->breadcrumbs
                    ->addBreadCrumb(t('Корзина'),$this->router->getUrl('shop-front-cartpage')) 
                    ->addBreadCrumb(t('Адрес и контакты'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'address'
                    ))) 
                    ->addBreadCrumb(t('Выбор доставки'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'delivery'
                    )))
                    ->addBreadCrumb(t('Выбор склада'));
        
        if ( $this->order['expired'] || !$this->order->getCart() ) $this->redirect();
        
        $this->view->assign(array(
            'warehouses_list' => $warehouses
        ));
        
        if ($this->url->isPost()){  
            $this->order_api->addOrderExtraDataByStep($this->order, 'warehouses', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные
            $sysdata = array('step' => 'warehouses');            
            $work_fields = $this->order->useFields($sysdata + $this->url->getSource(POST));
            $this->order->setCheckFields($work_fields);
            if ($this->order->checkData($sysdata, null, null, $work_fields)) {
               $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'payment')));
            }
        }
        
        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));
        
        return $this->result->setTemplate( 'checkout/warehouse.tpl' );   
    }
    
    
    /**
    * Шаг 3. Выбор оплаты
    */
    function actionPayment()
    {
        $this->app->title->addSection(t('Выбор оплаты'));

        //Добавим хлебные крошки
        $this->app->breadcrumbs
                    ->addBreadCrumb(t('Корзина'),$this->router->getUrl('shop-front-cartpage')) 
                    ->addBreadCrumb(t('Адрес и контакты'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'address'
                    ))); 
        if (!$this->getModuleConfig()->hide_delivery) {
                    $this->app->breadcrumbs->addBreadCrumb(t('Выбор доставки'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'delivery'
                    )));
        }
        $this->app->breadcrumbs->addBreadCrumb(t('Выбор оплаты'));

        if ( $this->order['expired'] || !$this->order->getCart() ) $this->redirect();

        $pay_api = new \Shop\Model\PaymentApi();
        $payment_list = $pay_api->getCheckoutPaymentList($this->user, $this->order);
        
        $this->view->assign(array(
            'pay_list' => $payment_list
        ));
        
        //Найдём оплату по умолчанию, если оплата не была задана раннее
        if (!$this->order['payment']){
            $pay_api->setFilter('default_payment', 1);
            $default_payment = $pay_api->getFirst($this->order);
            if ($default_payment){
                $this->order['payment'] = $default_payment['id'];
            } 
        }
        
        if ($this->getModuleConfig()->hide_payment) { //Если нужно проскочить шаг оплата
            $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'confirm'))); 
        }
        
        if ($this->url->isPost()) {
            $this->order_api->addOrderExtraDataByStep($this->order, 'pay', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные 
            $sysdata = array('step' => 'pay');            
            $work_fields = $this->order->useFields($sysdata + $_POST);
            $this->order->setCheckFields($work_fields);
            if ($this->order->checkData($sysdata, null, null, $work_fields)) {

                // Фиксация события "Выбран способ оплаты" для статистики
                \RS\Event\Manager::fire('statistic', array('type' => StatisticEvents::TYPE_SALES_SELECT_PAYMENT_METHOD));

                $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'confirm')));
            }        
        }
        
        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));
        
        return $this->result->setTemplate( 'checkout/payment.tpl' );
    }
    
    /**
    * Шаг 4. Подтверждение заказа
    */
    function actionConfirm()
    {
        $this->app->title->addSection(t('Подтверждение заказа'));

        if ( $this->order['expired'] || !$this->order->getCart() ) $this->redirect();
        
        $basket = $this->order->getCart();
        \RS\Event\Manager::fire('checkout.confirm', array(
            'order' => $this->order,
            'cart' => $basket
        ));
        
        //Добавим хлебные крошки
        $this->app->breadcrumbs
                    ->addBreadCrumb(t('Корзина'),$this->router->getUrl('shop-front-cartpage')) 
                    ->addBreadCrumb(t('Адрес и контакты'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'address'
                    )));
        if (!$this->getModuleConfig()->hide_delivery) {
            $this->app->breadcrumbs->addBreadCrumb(t('Выбор доставки'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'delivery'
                    )));
        }
        if (!$this->getModuleConfig()->hide_payment) { 
            $this->app->breadcrumbs->addBreadCrumb(t('Выбор оплаты'),$this->router->getUrl('shop-front-checkout',array(
                        'Act' => 'payment'
                    )));
        }
        $this->app->breadcrumbs->addBreadCrumb(t('Подтверждение заказа'));
        
        $this->view->assign(array(
            'cart' => $basket
        ));
        
        if ($this->url->isPost()) {
            $this->order_api->addOrderExtraDataByStep($this->order, 'confirm', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные 
            
            $this->order->clearErrors();
            if ($this->getModuleConfig()->require_license_agree && !$this->url->post('iagree', TYPE_INTEGER)) {
                $this->order->addError(t('Подтвердите согласие с условиями предоставления услуг'));
            }
            
            $sysdata = array('step' => 'confirm');
            $work_fields = $this->order->useFields($sysdata + $_POST);
            
            $this->order->setCheckFields($work_fields);
            if (!$this->order->hasError() && $this->order->checkData($sysdata, null, null, $work_fields)) {
                $this->order['is_payed'] = 0;
                $this->order['delivery_new_query'] = 1;
                $this->order['payment_new_query'] = 1;
               
                //Создаем заказ в БД
                if ($this->order->insert()) {
                    // Фиксация события "Подтверждение заказа" для статистики
                    \RS\Event\Manager::fire('statistic', array('type' => StatisticEvents::TYPE_SALES_CONFIRM_ORDER));

                    $this->order['expired'] = true; //заказ уже оформлен. больше нельзя возвращаться к шагам.
                    \Shop\Model\Cart::currentCart()->clean(); //Очищаем корзиу                    
                    $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'finish')));
                }
            }
        }
        
        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));
        
        return $this->result->setTemplate( 'checkout/confirm.tpl' );
    }
    
    /**
    * Шаг 5. Создание заказа
    */
    function actionFinish()
    {
        $this->app->title->addSection(t('Заказ №%0 успешно оформлен',array($this->order->order_num)));
        
        //Добавим хлебные крошки
        $this->app->breadcrumbs
                    ->addBreadCrumb(t('Корзина')) 
                    ->addBreadCrumb(t('Адрес и контакты')) 
                    ->addBreadCrumb(t('Выбор доставки'))
                    ->addBreadCrumb(t('Выбор оплаты'))
                    ->addBreadCrumb(t('Завершение заказа'));
        
        $this->view->assign(array(
            'cart' => $this->order->getCart(),
            'alt' => 'alt',
            'statuses' => \Shop\Model\UserStatusApi::getStatusIdByType()
        ));
        
        return $this->result->setTemplate( 'checkout/finish.tpl' );
    }
    
    /**
    * Выполняет пользовательский статический метод у типа оплаты или доставки, 
    * если таковой есть у типа доставки 
    */
    function actionUserAct()
    {
        $module   = $this->request('module',TYPE_STRING, 'Shop'); //Имя модуля
        $type_obj = $this->request('typeObj',TYPE_INTEGER,0);     //0 - доставка (DeliveryType), 1 - оплата (PaymentType)
        $type_id  = $this->request('typeId',TYPE_INTEGER,0);      //id доставки или оплаты
        $class    = $this->request('class',TYPE_STRING,false);    //Класс для обращения
        $act      = $this->request('userAct',TYPE_STRING,false);  //Статический метод который нужно вызвать 
        $params   = $this->request('params',TYPE_ARRAY,array());  //Дополнительные параметры для передачи в метод
       
        if ($module && $act && $class){
           $typeobj = "DeliveryType"; 
           if ($type_obj == 1){
              $typeobj = "PaymentType";
           } 
            
           $delivery = '\\'.$module.'\Model\\'.$typeobj.'\\'.$class;     
           $data = $delivery::$act($this->order, $type_id, $params);
           
           if (!$this->order->hasError()){
              return $this->result->setSuccess(true)
                     ->addSection('data',$data);  
           }else{
              return $this->result->setSuccess(false)
                    ->addEMessage($this->order->getErrorsStr());   
           }
        }else{
           return $this->result->setSuccess(false)
                    ->addEMessage(t('Не установлен метод или объект доставки или оплаты')); 
        }
    }
    
    /**
    * Удаление адреса при оформлении заказа
    */
    function actionDeleteAddress()
    {
        $id = $this->url->request('id', TYPE_INTEGER, 0); //id адреса доставки
        if ($id){
           $address = new \Shop\Model\Orm\Address($id); 
           if ($address['user_id'] == $this->user['id']) {
               $address['deleted'] = 1;
               $address->update();
               return $this->result->setSuccess(true);
           }
        }
        return $this->result->setSuccess(false);
    }
    
    
    /**
    * Подбирает город по совпадению в переданной строке
    */
    function actionSearchCity()
    {
        $query       = $this->request('term', TYPE_STRING, false);
        $region_id   = $this->request('region_id', TYPE_INTEGER, false);
        $country_id  = $this->request('country_id', TYPE_INTEGER, false);
        
        if ($query!==false && $this->url->isAjax()){ //Если задана поисковая фраза и это аякс
            $cities = $this->order_api->searchCityByRegionOrCountry($query, $region_id, $country_id);
            
            $result_json = array();  
            if (!empty($cities)){
                foreach ($cities as $city){
                    $region  = $city->getParent();
                    $country = $region->getParent();
                    $result_json[] = array(
                        'value'      => $city['title'],
                        'label'      => preg_replace("%($query)%iu", '<b>$1</b>', $city['title']),
                        'id'         => $city['id'],
                        'zipcode'    => $city['zipcode'],
                        'region_id'  => $region['id'],
                        'country_id' => $country['id']
                    );
                }
            }
            
            $this->wrapOutput(false);
            $this->app->headers->addHeader('content-type', 'application/json');
            return json_encode($result_json);
        }
    }
}