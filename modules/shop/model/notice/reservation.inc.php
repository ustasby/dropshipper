<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Notice;
/**
* Уведомление - оформлен предварительный заказ
*/
class Reservation extends \Alerts\Model\Types\AbstractNotice
                  implements \Alerts\Model\Types\InterfaceEmail, 
                             \Alerts\Model\Types\InterfaceSms,
                             \Alerts\Model\Types\InterfaceDesktopApp
{
    public
        $reserve;

    public function getDescription()
    {
        return t('Предварительный заказ (администратору)');
    } 
            
    function init(\Shop\Model\Orm\Reservation $reserve)
    {
        $this->reserve = $reserve;
    }
    
    function getNoticeDataEmail()
    {
        $site_config = \RS\Config\Loader::getSiteConfig(\RS\Site\Manager::getSiteId());
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        
        $notice_data->email     = $site_config['admin_email'];
        $notice_data->subject   = t('Предварительный заказ на сайте %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%shop%/notice/toadmin_reservation.tpl';
    }

    function getNoticeDataSms()
    {
        $site_config = \RS\Config\Loader::getSiteConfig();
        
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();
        
        if(!$site_config['admin_phone']) return;
        
        $notice_data->phone     = $site_config['admin_phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%shop%/notice/toadmin_reservation_sms.tpl';
    }
    
    /**
    * Возвращает путь к шаблону уведомления для Desktop приложения
    * 
    * @return string
    */
    public function getTemplateDesktopApp()
    {
        return '%shop%/notice/desktop_reservation.tpl';
    }
    
    /**
    * Возвращает данные, которые необходимо передать при инициализации уведомления
    * 
    * @return NoticeDataDesktopApp
    */
    public function getNoticeDataDesktopApp()
    {
        $desktop_data = new \Alerts\Model\Types\NoticeDataDesktopApp();
        $desktop_data->title = t('Предварительный заказ №%0', array($this->reserve->id));
        $desktop_data->short_message = t('%product %offer (Кол-во: %amount)', array(
            'product' => $this->reserve->product_title,
            'offer' => $this->reserve->offer,
            'amount' => $this->reserve->amount
        ));
        
        $desktop_data->link = \RS\Router\Manager::obj()->getAdminUrl('edit', array('id' => $this->reserve->id), 'shop-reservationctrl', true);
        $desktop_data->link_title = t('Перейти к заказу');
        
        $desktop_data->vars = $this;
        
        return $desktop_data;
    }
}