<?php

namespace Evasmart\Model\Behavior;

use RS\Behavior\BehaviorAbstract;
use RS\Orm\Request;

/**
 * Class CartItem
 *
 * @package Evasmart\Model\Behavior
 * @property
 */
class CartItem extends BehaviorAbstract
{

    /**
     * Установим цены дропшипера для покупателя
     *
     * @param $cost
     */
    public function updateDsCost($cost)
    {
        /**
         * @var $cartItem \Shop\Model\Orm\CartItem
         */
        $cartItem = $this->owner;
        $cartItem['ds_single_cost'] = $cost;
        $cartItem['ds_price'] = $cartItem['amount'] * $cost;

        Request::make()
            ->update($cartItem)
            ->set(array(
                'ds_single_cost' => $cartItem['ds_single_cost'],
                'ds_price' => $cartItem['ds_price']
            ))
            ->where(array(
                'site_id' => $cartItem['site_id'],
                'session_id' => $cartItem['session_id'],
                'uniq' => $cartItem['uniq'],
            ))->exec();
    }

    public function getDsSingleCost()
    {
        return $this->owner['ds_single_cost'];

    }
}