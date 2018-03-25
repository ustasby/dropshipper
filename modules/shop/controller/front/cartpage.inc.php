<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Shop\Controller\Front;
use Main\Model\StatisticEvents;

/**
* Просмотр корзины
*/
class CartPage extends \RS\Controller\Front
{
    /**
     * @var \Shop\Model\Cart $cart
     */
    public $cart;

    
    function init()
    {
        $this->cart = \Shop\Model\Cart::currentCart();
    }
    
    
    /**
    * Обычный просмотр товара
    */
    function actionIndex()
    {
        $add_product_id           = $this->url->request('add', TYPE_INTEGER);       //id товара
        $add_product_amount       = $this->url->request('amount', TYPE_FLOAT);    //Количество
        $add_product_offer        = $this->url->request('offer', TYPE_STRING);      //Комплектация
        $add_product_multioffers  = $this->url->request('multioffers', TYPE_ARRAY); //Многомерные комплектации
        $add_product_concomitants = $this->url->request('concomitant', TYPE_ARRAY); //Сопутствующие товары
        $add_product_concomitants_amount = $this->url->request('concomitant_amount', TYPE_ARRAY); //Количество сопутствующих твоаров
        $add_product_additional_uniq = $this->url->request('uniq', TYPE_STRING); // Дополнительный унификатор товара
        $checkout                 = $this->url->request('checkout', TYPE_BOOLEAN);

        $this->app->breadcrumbs->addBreadCrumb(t('Корзина'));
        
        if (!empty($add_product_id)) {
            
            $this->cart->addProduct($add_product_id, 
                                    $add_product_amount, 
                                    $add_product_offer, 
                                    $add_product_multioffers, 
                                    $add_product_concomitants,
                                    $add_product_concomitants_amount,
                                    $add_product_additional_uniq);
                                    
            if (!$this->url->isAjax()) {
                $this->redirect( $this->router->getUrl('shop-front-cartpage') );
            }
        }
        
        $cart_data = $this->cart->getCartData();
        
        $this->view->assign(array(
            'cart'      => $this->cart,
            'cart_data' => $cart_data, 
        ));
        
        if ($checkout && !$cart_data['has_error']){

            // Фиксация события "Начало оформления заказа" для статистики
            \RS\Event\Manager::fire('statistic', array('type' => StatisticEvents::TYPE_SALES_CART_SUBMIT));

            $this->result->setRedirect($this->router->getUrl('shop-front-checkout'));
        }
        
        return $this->result
                        ->addSection('cart', array(
                            'can_checkout'     => !$cart_data['has_error'],
                            'total_unformated' => $cart_data['total_unformatted'],
                            'total_price'      => $cart_data['total'],
                            'items_count'      => $cart_data['items_count']
                        ))
                        ->setTemplate( 'cartpage.tpl' );
    }
    
    /**
    * Обновляет информацию о товарах, их количестве в корзине. Добавляет купон на скидку, если он задан
    */
    function actionUpdate()
    {
        if ($this->url->isPost()) {
            $products     = $this->url->request('products', TYPE_ARRAY);
            $coupon       = trim($this->url->request('coupon', TYPE_STRING));
            $apply_coupon = $this->cart->update($products, $coupon);
            
            if ($apply_coupon !== true) {
                $this->cart->addUserError($apply_coupon, false, 'coupon');
                
                $this->view->assign(array(
                    'coupon_code' => $coupon,
                ));
            }
        }
        
        return $this->actionIndex();
    }
    
    /**
    * Удаляет товар из корзины
    */
    function actionRemoveItem()
    {
        $uniq = $this->url->request('id', TYPE_STRING);
        $success = $this->cart->removeItem($uniq);
        
        return $this->actionIndex();
    }
    
    /**
    * Очищает корзину
    */
    function actionCleanCart()
    {
        $success = $this->cart->clean();
        return $this->actionIndex();
    }

    /**
     * Повторяет предыдущий заказ
     *
     */
    function actionRepeatOrder()
    {
        $order_num = $this->url->request('order_num', TYPE_STRING, false); //Номер заказа

        if ($order_num){ //Если заказ найден, повторим его и переключимся в корзину
            $this->getCart()->repeatCartFromOrder($order_num);
        }
        $this->redirect($this->router->getUrl('shop-front-cartpage'));
    }
    
    /**
    * Возвращает корзину
    * 
    * @return \Shop\Model\Cart
    */
    function getCart()
    {
        return $this->cart;
    }
}