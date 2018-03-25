<?php

namespace Evasmart\Controller\Front;

use RS\Controller\AuthorizedFront;
use \RS\Application\Auth as AppAuth;
use Shop\Model\Cart;
use Shop\Model\Orm\Address;

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
        $this->order['order_type'] = 1;

        $this->order['expired'] = false;
        $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'address')));
    }

    function actionDefault()
    {
        $this->order->clear();

        $frozen_cart = \Shop\Model\Cart::preOrderCart(null);
        $frozen_cart->splitSubProducts();
        $frozen_cart->mergeEqual();

        $this->order->linkSessionCart($frozen_cart);
        $this->order->setCurrency(\Catalog\Model\CurrencyApi::getCurrentCurrency());

        $this->order['ip'] = $_SERVER['REMOTE_ADDR'];
        $this->order['order_type'] = 0;

        $this->order['expired'] = false;
        $this->redirect($this->router->getUrl('shop-front-checkout', array('Act' => 'address')));
    }


}