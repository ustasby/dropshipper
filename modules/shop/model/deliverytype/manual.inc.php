<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

/**
* Тип доставки - Устанавливаема вручную стоимость
*/
class Manual extends AbstractType implements 
                                        \Shop\Model\DeliveryType\InterfaceIonicMobile
{
    protected
        $form_tpl = 'type/fixed.tpl';
    
    function getTitle()
    {
        return t('Изменяемая вручную цена');
    }
    
    function getDescription()
    {
        return t('Стоимость рассчитывается менеджером, после оформления заказа');
    }
    
    function getShortName()
    {
        return 'manual';
    }
    
    /**
    * Возвращает стоимость доставки для заданного заказа. Только число.
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - адрес доставки
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @param boolean $use_currency - использовать валюту?
    * @return double
    */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        return 0;
    }

    /**
    * Возвращает цену в текстовом формате, т.е. здесь может быть и цена и надпись, например "Бесплатно"
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @return string
    */
    function getDeliveryCostText(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery)
    {
        $cost = $this->getDeliveryCost($order, $address, $delivery);
        return ($cost) ? CCustomView::cost($cost) : t('Будет рассчитана менеджером');
    }   
    
    /**
    * Возвращает HTML для приложения на Ionic
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    */
    function getIonicMobileAdditionalHTML(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        return "";    
    } 
}