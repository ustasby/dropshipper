<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Model\SMS\SMSUslugi;

class Sender extends \Alerts\Model\SMS\AbstractSender
{
    /**
    * Возвращает сокращенное название провайдера (только латинские буквы)
    * @return string
    */
    public function getShortName()
    {
        return 'smsuslugi';
    }
    
    /**
    * Возвращает отображаемое название провайдера
    * @return string
    */
    public function getTitle()
    {
        return t('СМС-услуги (sms.readyscript.ru)');
    }
    
    /**
    * Отправка SMS
    * 
    * @param string $text
    * @param array $phone_numbers
    */
    public function send($text, $phone_numbers)
    {
        $config = \RS\Config\Loader::byModule($this);
        
        // Если не указан логин, отправка не выполняется
        if(!$config['sms_sender_login']){
            return;
        }
        
        Transport::$HTTPS_LOGIN     = $config['sms_sender_login'];
        Transport::$HTTPS_PASSWORD  = $config['sms_sender_pass'];
        
        $transport = new Transport;
        $ret = $transport->send(array('text' => $text), $phone_numbers);
        
        if($ret['code'] != 1){
            throw new \Exception($ret['descr'], $ret['code']);
        }
    }
    
}