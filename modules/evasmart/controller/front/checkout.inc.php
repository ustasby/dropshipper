<?php

namespace Evasmart\Controller\Front;

use RS\Controller\AuthorizedFront;

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

        return $this->result->setTemplate('checkout/address.tpl');
    }

}