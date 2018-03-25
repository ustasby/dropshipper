<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Notice;

/**
* Уведомление - заказ был изменен
*/
class OrderChange extends \Alerts\Model\Types\AbstractNotice 
    implements \Alerts\Model\Types\InterfaceEmail, \Alerts\Model\Types\InterfaceSms
{
    public
        $order,
        $user;
        
    /**
    * Возвращает название уведомления
    *     
    */
    public function getDescription()
    {
        return t('Изменение заказа');
    } 
    
    /**
    * Инициализация уведомления
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */    
    function init(\Shop\Model\Orm\Order $order)
    {
        $this->order = $order;
        $this->user  = $order->getUser();
        $this->currency_api = new \Catalog\Model\CurrencyApi();
    }
    
    /**
    * Получаение информации о письме
    * 
    * @return \Alerts\Model\Types\NoticeDataEmail|false
    */
    function getNoticeDataEmail()
    {
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        
        $email_to_user = new \Alerts\Model\Types\NoticeDataEmail();
        
        if (filter_var($this->user['e_mail'], FILTER_VALIDATE_EMAIL)){ //Если задан пользовательский E-mail
            $notice_data->email = $this->user['e_mail']; 
        }else{ //Если пользовательского E-mail нет
            return false;
        }
    
        $notice_data->subject   = t('В заказе N%0 на сайте %1 произошли изменения', array($this->order['order_num'], \RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    /**
    * Возвращает шаблон письма
    * 
    * @return string
    */
    function getTemplateEmail()
    {
        return '%shop%/notice/touser_orderchange.tpl';
    }

    /**
    * Возвращает сведения об уведомлении на телефон
    * 
    * @return \Alerts\Model\Types\NoticeDataSms|false
    */
    function getNoticeDataSms()
    {
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();
        
        if(!$this->user['phone']) return false;
        
        $notice_data->phone     = $this->user['phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    /**
    * Возвращает шаблон SMS
    * 
    * @return string
    */
    function getTemplateSms()
    {
        return '%shop%/notice/touser_orderchange_sms.tpl';
    }
    
}