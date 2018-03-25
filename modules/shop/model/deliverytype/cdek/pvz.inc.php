<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType\Cdek;

/**
 * Класс отвечает за работу с Пунктами выдачи заказа
 */
class Pvz extends \Shop\Model\DeliveryType\Helper\Pvz
{
    protected $cash_on_delivery = 0;
    protected $note = ""; //Заметки

    /**
     * Вовзращает Ограничение оплаты наличными при получении
     *
     * @return mixed
     */
    function getCashOnDelivery()
    {
        return $this->cash_on_delivery;
    }

    /**
     * Устанавливает Ограничение оплаты наличными при получении
     *
     * @param string $cash_on_delivery
     */
    function setCashOnDelivery($cash_on_delivery)
    {
        $this->cash_on_delivery = $cash_on_delivery;
    }

    /**
     * Вовзращает заметки
     *
     * @return string
     */
    function getNote()
    {
        return $this->note;
    }

    /**
     * Устанавливает заметки
     *
     * @param string $note
     */
    function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Возвращает наименование пункта доставки
     *
     * @return string
     */
    function getPickPointTitle()
    {
        return implode(", ", array($this->getCity(), $this->getAddress()));
    }

    /**
     * Возвращает дополнительный HTML для показа при выборе пункта выдачи заказа
     *
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getAdditionalHTML()
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'pickpoint' => $this,
        ) + \RS\Module\Item::getResourceFolders($this));

        return $view->fetch("%shop%/delivery/cdek/pvz.tpl");
    }

    /**
     * Возвращает данные по ПВЗ, которые необходимы для оформления заказа
     *
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getDeliveryExtraJson()
    {
        $flags = defined('JSON_PRETTY_PRINT') ?  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : null;
        return json_encode(array(
                'code' => $this->getCode(),
                'addressInfo' => $this->getFullAddress(),
                'address' => $this->getAddress(),
                'city' => $this->getCity(),
                'phone' => $this->getPhone(),
                'coordX' => $this->getCoordX(),
                'coordY' => $this->getCoordY(),
                'info' => $this->getAdditionalHTML(),
                'note' => $this->getNote()
            ) + $this->getExtra(), $flags);
    }
}