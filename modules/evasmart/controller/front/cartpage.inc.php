<?php

namespace Evasmart\Controller\Front;

use RS\Controller\AuthorizedFront;

class CartPage extends AuthorizedFront
{

    //protected $need_group = 'DS';

    /**
     * @var \Shop\Model\Cart $cart
     */
    public $cart;

    function init()
    {
        $this->app->title->addSection(t('Оформление заказа'));
        $this->cart = \Shop\Model\Cart::currentCart();
    }

    function actionIndex()
    {
        $this->redirect($this->router->getUrl('evasmart-front-cart', array('Act' => 'update')));
    }

    /**
     * Установка цен дропшипера для покупателей
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionUpdate()
    {

        if ($this->url->isPost()) {
            $add_ds_single_cost = $this->url->post('ds_single_cost', TYPE_ARRAY);

            // добавим цену дропшипера для покупателя
            if (is_array($add_ds_single_cost)) {
                $cartItems = $this->cart->getItems();
                foreach ($cartItems as $cartItem) {
                    if (isset($add_ds_single_cost[$cartItem['uniq']])) {
                        $cartItem->updateDsCost($add_ds_single_cost[$cartItem['uniq']]); // behavior
                    }
                }
            }
            $this->redirect($this->router->getUrl('evasmart-front-checkout', array('Act' => 'index')));
        }

        $this->app->breadcrumbs->addBreadCrumb(t('Оформление заказа'));

        $cart_data = $this->cart->getCartData();

        $this->view->assign(array(
            'cart'      => $this->cart,
            'cart_data' => $cart_data,
        ));

        return $this->result
            ->addSection('cart', array(
                'total_unformated' => $cart_data['total_unformatted'],
                'total_price'      => $cart_data['total'],
                'items_count'      => $cart_data['items_count']
            ))
            ->setTemplate( 'cartpage_page.tpl' );
    }


}