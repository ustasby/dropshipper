<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class ProductsReturnApi extends \RS\Module\AbstractModel\EntityList implements \Main\Model\NoticeSystem\HasMeterInterface
{
    const METER_RETURN = 'rs-admin-menu-returns';
    public $return;

    /**
     * ProductsReturnApi constructor.
     */
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\ProductsReturn(), array(
            'multisite' => true,
            'defaultOrder' => 'dateof DESC'
        ));
        $this->return = $this->getElement();
    }


    function getMeterApi($user_id = null)
    {
        return new \Main\Model\NoticeSystem\MeterApi($this->obj_instance,
            self::METER_RETURN,
            $this->getSiteContext(),
            $user_id);
    }

    /**
     * Возвращает список товаров на возврат по id заказа
     *
     * @param integer $user_id - id пользователя
     * @return array
     * @throws \RS\Orm\Exception
     */
    function getReturnItemsByUserId($user_id)
    {
        return \RS\Orm\Request::make()
            ->select("OI.*")
            ->from(new \Shop\Model\Orm\ProductsReturnOrderItem(), "OI")
            ->join(new \Shop\Model\Orm\ProductsReturn(), "OI.return_id = R.id", "R")
            ->where(array(
                'R.user_id' => $user_id,
            ))
            ->objects(null, 'uniq');
    }

    /**
     * Возвращает список товаров на возврат по id заказа
     *
     * @param integer $order_id - id заказа
     * @return array
     * @throws \RS\Orm\Exception
     */
    function getReturnItemsByOrder($order_id)
    {
        return \RS\Orm\Request::make()
            ->select("OI.*")
            ->from(new \Shop\Model\Orm\ProductsReturnOrderItem(), "OI")
            ->join(new \Shop\Model\Orm\ProductsReturn(), "OI.return_id = R.id", "R")
            ->where(array(
                'R.order_id' => $order_id,
            ))
            ->objects(null, 'uniq');
    }

    /**
     * Возвращает массив возвратов
     *
     * @param integer $user_id - идентификатор пользователя
     *
     * @return array
     */
    function getReturnsByUserId($user_id)
    {
        $this->setFilter('user_id', $user_id);
        return $this->getList();
    }
}