<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Alerts\Model\SMS;

class Manager
{
    /**
    * Отправляет SMS сообщение
    * 
    * @param array | string $phone_numbers - массив телефонов, или строка телефонов, разделенных запятой 
    * @param string $tpl - путь к шаблону сообщения
    * @param mixed $data - параметры, передаваемые в шаблон
    * @param bool $suppress_exception - если true, по подавляет исключения
    * @return void
    */
    public static function send($phone_numbers, $tpl, $data, $suppress_exception = true)
    {
        if(!is_array($phone_numbers)){
            $phone_numbers = explode(',', (string)$phone_numbers);
        }
        
        $phone_numbers = array_map('trim', $phone_numbers);
        
        $config = \RS\Config\Loader::byModule('alerts');
        
        $view = new \RS\View\Engine();
        $view->assign('data', $data);
        $content = $view->fetch($tpl);
        
        $sender_class = $config['sms_sender_class'];
        $api = new \Alerts\Model\Api();
        
        if ($sender_class){
            /**
            * @var AbstractSender
            */
            if ($sender = $api->getSenderByShortName($sender_class)) {
                try {
                    return $sender->send($content, $phone_numbers);
                } catch (\Exception $e) {
                    if (!$suppress_exception) {
                        throw $e;
                    }
                }
            }
        }
        

    }
}