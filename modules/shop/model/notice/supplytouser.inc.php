<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Notice;
/**
* Уведомление - Заказанный товар поступил на склад
*/
class SupplyToUser extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceEmail, \Alerts\Model\Types\InterfaceSms
{
    public
        $reserve;

    public function getDescription()
    {
        return t('Уведомление о поступлении заказа на склад.');
    } 
            
    function init(\Shop\Model\Orm\Reservation $reserve)
    {
        $this->reserve = $reserve;
    }
    
    function getNoticeDataEmail()
    {   
        $site_config = \RS\Config\Loader::getSiteConfig(\RS\Site\Manager::getSiteId());
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        
        if (!$this->reserve['email']) return;
        
        $notice_data->email     = $this->reserve['email']; 
        $notice_data->subject   = t('Поступление товара на склад, заказ на сайте %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%shop%/notice/touser_reservation.tpl';
    }

    function getNoticeDataSms()
    {
        $site_config = \RS\Config\Loader::getSiteConfig();
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();
        
        if(!$this->reserve['phone']) return;
        
        $notice_data->phone     = $this->reserve['phone'];
        $notice_data->vars      = $this;
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%shop%/notice/touser_reservation_sms.tpl';
    }
}

