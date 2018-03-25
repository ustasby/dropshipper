<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PaymentType;

/**
* Тип оплаты - наличные
*/
class Cash extends AbstractType
{

   /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Оплата наличными');
    }
    
    /**
    * Возвращает описание типа оплаты для администратора. Возможен HTML
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Метод не предусматривает никакого процессинга');
    }
    
    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'cash';
    }
    
    /**
    * Возвращает true, если данный тип поддерживает проведение платежа через интернет
    * 
    * @return bool
    */
    function canOnlinePay()
    {
        return false;
    }
}

?>
