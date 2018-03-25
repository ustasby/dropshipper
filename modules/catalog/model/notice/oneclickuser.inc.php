<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Notice;
/**
* Уведомление - купить в один клик пользователю
*/
class OneClickUser extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceSms
{
    public
        $oneclick;

    public function getDescription()
    {
        return t('Купить в один клик (пользователю)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param array $oneclick  - массив с параметрами для передачи 
    * @return void
    */
    function init($oneclick)
    {
        $this->oneclick = $oneclick;
    }

    function getNoticeDataSms()
    {
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();
        
        $notice_data->phone     = $this->oneclick['phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%catalog%/notice/touser_oneclick_sms.tpl';
    }
}

