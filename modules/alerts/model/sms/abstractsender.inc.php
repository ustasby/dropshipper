<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Alerts\Model\SMS;

abstract class AbstractSender
{
    /**
    * Возвращает сокращенное название провайдера (только латинские буквы)
    * @return string
    */
    abstract public function getShortName();
    
    /**
    * Возвращает отображаемое название провайдера
    * @return string
    */
    abstract public function getTitle();
    
    /**
    * Отправка SMS
    * 
    * @param string $text
    * @param array $phone_numbers
    */
    abstract public function send($text, $phone_numbers);
}