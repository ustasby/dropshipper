<?php

namespace Evasmart\Controller\Front;

use RS\Controller\AuthorizedFront;
use \RS\Application\Auth as AppAuth;

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


    }

}