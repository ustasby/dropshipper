<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType\Helper;

/**
 * Класс отвечает за работу с Пунктами выдачи заказа
 */
class Pvz
{
    protected $code;
    protected $title;
    protected $country;
    protected $region;
    protected $city;
    protected $address;
    protected $worktime;
    protected $coord_x;
    protected $coord_y;
    protected $phone;
    protected $extra = array();

    /**
     * Возвращает код пункта выдачи заказа
     *
     * @return mixed
     */
    function getCode()
    {
        return $this->code;
    }

    /**
     * Установка кода пункта выдачи заказа
     *
     * @param mixed $code
     */
    function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Возвращает название пункта выдачи
     *
     * @return mixed
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * Установка названия пункта выдачи
     *
     * @param mixed $title
     */
    function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Возвращает страну
     *
     * @return mixed
     */
    function getCountry()
    {
        return $this->country;
    }

    /**
     * Устанавливает страну
     *
     * @param mixed $country
     */
    function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Возвращает регион
     *
     * @return mixed
     */
    function getRegion()
    {
        return $this->region;
    }

    /**
     * Установка региона
     *
     * @param mixed $region
     */
    function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * Возвращает город
     *
     * @return mixed
     */
    function getCity()
    {
        return $this->city;
    }

    /**
     * Устанавливает город
     *
     * @param mixed $city
     */
    function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Возвращает адрес
     *
     * @return mixed
     */
    function getAddress()
    {
        return $this->address;
    }

    /**
     * Установка адреса
     *
     * @param mixed $address
     */
    function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Вовзращает время работы
     *
     * @return mixed
     */
    function getWorktime()
    {
        return $this->worktime;
    }

    /**
     * Устанавливает время работы
     *
     * @param mixed $worktime
     */
    function setWorktime($worktime)
    {
        $this->worktime = $worktime;
    }

    /**
     * Вовзращает телефон
     *
     * @return mixed
     */
    function getPhone()
    {
        return $this->phone;
    }

    /**
     * Устанавливает телефон
     *
     * @param string $phone
     */
    function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Возвращает координату X
     *
     * @return mixed
     */
    function getCoordX()
    {
        return $this->coord_x;
    }

    /**
     * Устанавливает координату X
     *
     * @param mixed $coord_x
     */
    function setCoordX($coord_x)
    {
        $this->coord_x = $coord_x;
    }

    /**
     * Возвращает координату Y
     *
     * @return mixed
     */
    function getCoordY()
    {
        return $this->coord_y;
    }

    /**
     * Устанавливает координату Y
     *
     * @param mixed $coord_y
     */
    function setCoordY($coord_y)
    {
        $this->coord_y = $coord_y;
    }

    /**
     * Возвращает дополнительные данные
     *
     * @return mixed
     */
    function getExtra()
    {
        return $this->extra;
    }

    /**
     * Устанавливает дополнительные данные
     *
     * @param mixed $extra
     */
    function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * Возвращает наименование пункта доставки
     *
     * @return string
     */
    function getPickPointTitle()
    {
        return $this->getAddress();
    }

    /**
     * Возвращает дополнительный HTML для показа при выборе пункта выдачи заказа
     *
     * @return string
     */
    function getAdditionalHTML()
    {
        return "";
    }

    /**
     * Возвращает полный адрес пункта
     *
     * @return string
     */
    function getFullAddress()
    {
        $full_address = array($this->getCountry(), $this->getRegion(), $this->getCity(), $this->getAddress());
        $full_address = array_diff($full_address, array(null, ''));
        return implode(', ', $full_address);
    }

    /**
     * Возвращает данные по ПВЗ, которые необходимы для оформления заказа
     *
     * @return string
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
                'worktime' => $this->getWorktime(),
                'coordX' => $this->getCoordX(),
                'coordY' => $this->getCoordY(),
                'info' => $this->getAdditionalHTML()
            ) + $this->getExtra(), $flags);
    }
}