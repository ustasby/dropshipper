<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Model\Types;

/**
* Объект с обязательными параметрами для уведомления
*/
class NoticeDataEmail
{
    /**
    * Тема письма
    * 
    * @var string
    */
    public  $subject;
    
    /**
    * Адрес получателя
    * 
    * @var string
    */
    public  $email;
    
    /**
    * Данные, передаваемы в шаблон
    * 
    * @var mixed
    */
    public  $vars;
}

