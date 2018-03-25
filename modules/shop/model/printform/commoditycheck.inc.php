<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PrintForm;

/**
* Обычная печатная форма товарного чека
*/
class CommodityCheck extends AbstractPrintForm
{
    /**
    * Возвращает краткий символьный идентификатор печатной формы
    * 
    * @return string
    */
    function getId()
    {
        return 'commoditycheck';
    }
    
    /**
    * Возвращает название печатной формы
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Товарный чек');
    }
    
    /**
    * Возвращает шаблон формы
    * 
    * @return string
    */
    function getTemplate()
    {
        return '%shop%/printform/commoditycheck.tpl';
    }
}
