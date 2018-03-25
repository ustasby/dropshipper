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
* Тип доставки - Самовывоз. стоимость - 0
*/
class Myself extends AbstractType implements 
                                        \Shop\Model\DeliveryType\InterfaceIonicMobile
{

    /**
    * Возвращает название
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Самовывоз');
    }
    /**
     * Возвращает ORM объект для генерации формы или null
     *
     * @return \RS\Orm\FormObject | null
     */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'myself_addr' => new Type\Integer(array(
                'description' => t('Месторасположение пункта самовывоза'),
                'maxLength' => '11',
                'list' => array(array('\Shop\Model\RegionApi', 'selectListByOnlyCityGroupTree')),
            )),
            'pvz_list' => new Type\ArrayList(array(
                'description' => t('Доступные пункты самовывоза'),
                'hint' => t('Если не указаны, используются все пуннкты'),
                'list' => array(array('\Catalog\Model\WareHouseApi', 'staticPickupPointsSelectList'), array(0 => t('- Все -'))),
                'runtime' => false,
                'attr' => array(array(
                    'multiple' => true
                )),
            )),
        ));

        return new \RS\Orm\FormObject($properties);
    }

    /**
    * Возвращает описание
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Не предполагает взимание оплаты');
    }
    
    /**
    * Возвращает короткое системное имя
    * 
    * @return string
    */
    function getShortName()
    {
        return 'myself';
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
        $cost = $this->applyExtraChangeDiscount($delivery, 0); //Добавим наценку или скидку 
        if ($use_currency){
            $cost = $order->applyMyCurrency($cost);
        } 
        return $cost;
    }
    
    /**
    * Возвращает true, если тип доставки предполагает самовывоз
    * 
    * @return bool
    */
    function isMyselfDelivery()
    {
        return true;
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