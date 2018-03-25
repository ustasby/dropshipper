<?php
namespace Evasmart\Config;

use Catalog\Model\Orm\Product;
use Shop\Model\Orm\CartItem;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\OrderItem;
use Users\Model\Orm\User;


class Install extends \RS\Module\AbstractInstall
{

    function install()
    {
        $result = parent::install();
        if ($result) {
            $product = new Product();
            if (!isset($product['type_product'])) {
                Handlers::ormInitCatalogProduct($product);
                $product->dbUpdate();
            }

            $user = new User();
            if (!isset($product['manager_user_id'])) {
                Handlers::ormInitUsersUser($user);
                $user->dbUpdate();
            }

            $order = new Order();
            if (!isset($order['price_buyer'])) {
                Handlers::ormInitShopOrder($order);
                $order->dbUpdate();
            }

            // цены дропшипера
            $orderItem = new OrderItem();
            if (!isset($order['ds_single_cost']) || !isset($order['ds_price'])) {
                Handlers::ormInitShopOrderItem($orderItem);
                $orderItem->dbUpdate();
            }

            // цены дропшипера
            $cartItem = new CartItem();
            if (!isset($cartItem['ds_single_cost']) || !isset($cartItem['ds_price'])) {
                Handlers::ormInitShopCartItem($cartItem);
                $cartItem->dbUpdate();
            }

        }
        return $result;

    }

    function update()
    {
        $result = parent::update();
        if ($result) {
            $product = new Product();
            if (!isset($product['processed'])) {
                Handlers::ormInitCatalogProduct($product);
            }
            $product->dbUpdate();

            $user = new User();
            if (!isset($product['manager_user_id'])) {
                Handlers::ormInitUsersUser($user);
                $user->dbUpdate();
            }
            $order = new Order();
            if (!isset($order['price_buyer'])) {
                Handlers::ormInitShopOrder($order);
                $order->dbUpdate();
            }

            // цены дропшипера
            $orderItem = new OrderItem();
            if (!isset($order['ds_single_cost']) || !isset($order['ds_price'])) {
                Handlers::ormInitShopOrderItem($orderItem);
                $orderItem->dbUpdate();
            }

            // цены дропшипера
            $cartItem = new CartItem();
            if (!isset($cartItem['ds_single_cost']) || !isset($cartItem['ds_price'])) {
                Handlers::ormInitShopCartItem($cartItem);
                $cartItem->dbUpdate();
            }

        }
        return $result;
    }

}