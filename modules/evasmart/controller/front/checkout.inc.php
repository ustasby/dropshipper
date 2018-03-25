<?php

namespace Evasmart\Controller\Front;

use RS\Controller\AuthorizedFront;
use \RS\Application\Auth as AppAuth;
use Shop\Model\Cart;

class Checkout extends AuthorizedFront
{
    protected $need_group = 'DS';

    /**
     * @var \Shop\Model\OrderApi $order_api
     */
    public $order_api;
    /**
     * @var \Shop\Model\Orm\Order $order
     */
    public $order;

    function init()
    {
        $this->app->title->addSection(t('Оформление заказа дропшипера'));
        $this->order = \Shop\Model\Orm\Order::currentOrder();
        $this->order_api = new \Shop\Model\OrderApi();

        $this->order->clearErrors();
        $this->view->assign('order', $this->order);
    }

    function actionIndex()
    {
        $this->order->clear();

        $frozen_cart = \Shop\Model\Cart::preOrderCart(null);
        $frozen_cart->splitSubProducts();
        $frozen_cart->mergeEqual();

        $this->order->linkSessionCart($frozen_cart);
        $this->order->setCurrency(\Catalog\Model\CurrencyApi::getCurrentCurrency());

        $this->order['ip'] = $_SERVER['REMOTE_ADDR'];
        $this->order['warehouse'] = 0;

        $this->order['expired'] = false;
        $this->redirect($this->router->getUrl('evasmart-front-checkout', array('Act' => 'address')));
    }


    function actionAddress()
    {
        if (!$this->order->getCart()) {
            $this->redirect();
        }
        $this->app->title->addSection(t('Адрес и контакты покупателя'));

        $this->app->breadcrumbs
            ->addBreadCrumb(t('Оформление дропшипера'));

        $cart_data = $this->order['basket'] ? $this->order->getCart()->getCartData() : null;
        if ($cart_data === null || !count($cart_data['items']) || $cart_data['has_error'] || $this->order['expired']) {
            //Если корзина пуста или заказ уже оформлен или имеются ошибки в корзине, то выполняем redirect на главную сайта
            $this->redirect();
        }

        //Запрашиваем дополнительные поля формы заказа, если они определены в конфиге модуля
        $order_fields_manager  = $this->order->getFieldsManager();
        $order_fields_manager->setValues($this->order['userfields_arr']);


        if ($this->url->isPost()) { //POST
            $this->order['only_pickup_points'] = 0;
            $this->order_api->addOrderExtraDataByStep($this->order, 'address', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные
            $sysdata = array('step' => 'address');
            $work_fields = $this->order->useFields($sysdata + $_POST);
            $this->order['user_phone'] = $this->request('user_phone', TYPE_STRING);



            $this->order->setCheckFields($work_fields);
            $this->order->checkData($sysdata, null, null, $work_fields);
            $this->order['userfields'] = serialize($this->order['userfields_arr']);

            $this->order['user_type'] = '';
            $this->order['__code']->setEnable(false);

            //Сохраняем дополнительные сведения
            $uf_err = $order_fields_manager->check($this->order['userfields_arr']);
            if (!$uf_err) {
                //Переносим ошибки в объект order
                foreach($order_fields_manager->getErrors() as $form=>$errortext) {
                    $this->order->addError($errortext, $form);
                }
            }

            if (!$this->order->hasError()) {
                $this->order['user_id'] = AppAuth::getCurrentUser()->id;
                $this->redirect($this->router->getUrl('evasmart-front-checkout', array('Act' => 'delivery')));
            }
        }

        $user = AppAuth::getCurrentUser();

        $this->view->assign(array(
            'is_auth'         => AppAuth::isAuthorize(),
            'order'           => $this->order,
            'order_extra'     => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
            'user'            => $user,
            'conf_userfields' => $order_fields_manager,
        ));

        return $this->result->setTemplate('checkout/address.tpl');
    }

    function actionDelivery()
    {
        $delivery_api = new \Shop\Model\DeliveryApi();
        $delivery_list = $delivery_api->getCheckoutDeliveryList($this->user, $this->order);


        foreach ($delivery_list as $id => $delivery) {
            if ($delivery['title'] == 'Самовывоз') {
                unset($delivery_list[$id]);
            }

        }

        // Если доставка ещё не выбрана - выбираем первую доставку по умолчанию
        if (empty($this->order['delivery'])) {
            foreach ($delivery_list as $delivery) {

                if ($delivery['default']) {
                    $this->order['delivery'] = $delivery['id'];
                    break;
                }
            }
        }
        $this->app->title->addSection(t('Выбор доставки'));

        //Добавим хлебные крошки
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Корзина'),$this->router->getUrl('evasmart-front-cartpage'))
            ->addBreadCrumb(t('Адрес и контакты'), $this->router->getUrl('evasmart-front-checkout',array(
                'Act' => 'address'
            )))
            ->addBreadCrumb(t('Выбор доставки'));

        if ($this->order['expired'] || !$this->order->getCart()) {
            $this->redirect();
        }

        if ($this->url->isPost()) {
            $this->order_api->addOrderExtraDataByStep($this->order, 'delivery', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные

            //Проверим параметры выбора доставки
            $sysdata = array('step' => 'delivery');
            $work_fields = $this->order->useFields($sysdata + $this->url->getSource(POST));
            $this->order->setCheckFields($work_fields);
            if ($this->order->checkData($sysdata, null, null, $work_fields)) {

                $delivery       = $this->order->getDelivery(); //Выбранная доставка
                $delivery_extra = $this->request('delivery_extra',TYPE_ARRAY,false);
                if ($delivery_extra){
                    $this->order->addExtraKeyPair('delivery_extra', $delivery_extra);
                }

                if ($delivery['class'] == 'myself'){ //Если самовывоз и складов больше одного
                    //$this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'warehouses')));
                } else{
                    $this->redirect($this->router->getUrl('evasmart-front-checkout', array('Act' => 'payment')));
                }
            }
        }

        $this->view->assign(array(
            'delivery_list' => $delivery_list
        ));

        return $this->result->setTemplate( 'checkout/delivery.tpl' );
    }

    function actionPayment()
    {

        $this->app->title->addSection(t('Выбор оплаты'));

        //Добавим хлебные крошки
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Корзина'),$this->router->getUrl('evasmart-front-cartpage'))
            ->addBreadCrumb(t('Адрес и контакты'),$this->router->getUrl('evasmart-front-checkout',array(
                'Act' => 'address'
            )));
        $this->app->breadcrumbs->addBreadCrumb(t('Выбор оплаты'));

        if ($this->order['expired'] || !$this->order->getCart()) {
            $this->redirect();
        }

        $pay_api = new \Shop\Model\PaymentApi();
        $payment_list = $pay_api->getCheckoutPaymentList($this->user, $this->order);

        foreach ($payment_list as $id => $payment) {
            if ($payment['title'] !== 'Списать с баланса') {
                unset($payment_list[$id]);
            } else {
                $this->order['payment'] = $payment['id'];
            }
        }

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

        if ($this->url->isPost()) {
            $this->order_api->addOrderExtraDataByStep($this->order, 'pay', $this->url->request('order_extra', TYPE_ARRAY, array())); //Заносим дополнительные данные
            $sysdata = array('step' => 'pay');
            $work_fields = $this->order->useFields($sysdata + $_POST);
            $this->order->setCheckFields($work_fields);
            if ($this->order->checkData($sysdata, null, null, $work_fields)) {

                $this->redirect($this->router->getUrl('evasmart-front-checkout', array('Act' => 'confirm')));
            }
        }

        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));

        return $this->result->setTemplate('checkout/payment.tpl');
    }

    function actionConfirm()
    {
        $this->app->title->addSection(t('Подтверждение заказа'));

        if ($this->order['expired'] || !$this->order->getCart()) {
            $this->redirect();
        }

        $basket = $this->order->getCart();


        //Добавим хлебные крошки
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Корзина'),$this->router->getUrl('evasmart-front-cartpage'))
            ->addBreadCrumb(t('Адрес и контакты'),$this->router->getUrl('evasmart-front-checkout',array(
                'Act' => 'address'
            )));

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
                    $this->order['expired'] = true; //заказ уже оформлен. больше нельзя возвращаться к шагам.
                    Cart::currentCart()->clean(); //Очищаем корзиу
                    $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'finish')));
                }
            }


        }

        $this->view->assign(array(
            'order_extra' => !empty($this->order['order_extra']) ? $this->order['order_extra'] : array(),
        ));

        return $this->result->setTemplate('checkout/confirm.tpl');
    }


}