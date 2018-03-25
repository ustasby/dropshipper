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
* Тип доставки - Фиксированная цена доставки
*/
class FixedPay extends AbstractType implements 
                                        \Shop\Model\DeliveryType\InterfaceIonicMobile
{
    protected
        $form_tpl = 'type/fixed.tpl';
    
    function getTitle()
    {
        return t('Фиксированная цена');
    }
    
    function getDescription()
    {
        return t('Стоимость доставки не зависит ни от каких параметров');
    }
    
    function getShortName()
    {
        return 'fixedpay';
    }
    
    function getFormObject()
    {
        return new \RS\Orm\FormObject(new \RS\Orm\PropertyIterator(array(
            'cost' => new Type\Real(array(
                'description' => t('Стоимость')
            ))
        )));
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
        $cost = $this->opt['cost'];
        $cost = $this->applyExtraChangeDiscount($delivery, $cost); //Добавим наценку или скидку 
        if ($use_currency){
            $cost = $order->applyMyCurrency($cost);
        } 
        return $cost;
    }
    
    /**
    * Возвращает HTML для приложения на Ionic
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @return string
    */
    function getIonicMobileAdditionalHTML(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        return "";    
    }
}