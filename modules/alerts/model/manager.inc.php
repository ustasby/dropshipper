<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Model;

/**
* Менеджер уведомлений
*/
class Manager
{
    /**
    * Отправляет уведомление по классу событий
    * 
    * @param $notice object событие
    */
    public static function send(\Alerts\Model\Types\AbstractNotice $notice)
    {
        $notice_config = \Alerts\Model\Api::getNoticeConfig(get_class($notice));
        
        // Отправка E-Mail уведомления
        if($notice instanceof \Alerts\Model\Types\InterfaceEmail && $notice_config['enable_email']){
            $notice_data = $notice->getNoticeDataEmail();
            if ($notice_config->additional_recipients && $notice_data ) {
                $notice_data->email .= ','.$notice_config->additional_recipients;
            }
            if($notice_data){
                $mailer = new \RS\Helper\Mailer();
                $mailer->Subject = $notice_data->subject;
                $mailer->addEmails($notice_data->email);
                $mailer->renderBody($notice_config->template_email ? $notice_config->template_email : $notice->getTemplateEmail(), $notice_data->vars);
                $mailer->setEventParams('alerts', array('notice' => $notice));
                $mailer->send();
            }
        }

        // Отправка SMS уведомления
        if($notice instanceof \Alerts\Model\Types\InterfaceSms && $notice_config['enable_sms']){
            $notice_data = $notice->getNoticeDataSms();
            if($notice_data){
                \Alerts\Model\SMS\Manager::send(
                    $notice_data->phone, 
                    $notice->getTemplateSms(), 
                    $notice_data->vars
                );
            }
        }
        
        //Отправка уведомления в Desktop приложение ReadyScript
        if ($notice instanceof \Alerts\Model\Types\InterfaceDesktopApp && $notice_config['enable_desktop']) {

            $template = $notice_config->template_desktop ? $notice_config->template_desktop : $notice->getTemplateDesktopApp();
            
            NoticeItemsApi::cleanOldNoticeItems();
            NoticeItemsApi::addNoticeItem($notice, $template);
        }
    }
}
