<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Alerts\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля
*/
class File extends \RS\Orm\ConfigObject
{

    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'sms_sender_class' => new Type\Varchar(array(
                    'description' => t('SMS провайдер'),
                    'List' => array(array(new \Alerts\Model\Api(), 'selectSendersList')),
                )),
                'sms_sender_login' => new Type\Varchar(array(
                    'description' => t('Логин'),
                )),
                'sms_sender_pass' => new Type\Varchar(array(
                    'description' => t('Пароль'),
                )),
            t('Desktop уведомления'),
                'notice_items_delete_hours' => new Type\Integer(array(
                    'description' => t('Количество часов, которое следует хранить уведомления')
                )),
                'allow_user_groups' => new Type\ArrayList(array(
                    'runtime' => false,            
                    'description' => t('Группы пользователей, для которых доступно данное приложение'),
                    'list' => array(array('\Users\Model\GroupApi','staticSelectList')),
                    'size' => 7,
                    'attr' => array(array('multiple' => true))
                ))
        ));        

    }
       
    /**
    * Возвращает значения свойств по-умолчанию
    * 
    * @return array
    */
    public static function getDefaultValues()
    {
        return 
            parent::getDefaultValues() + array(
                'tools' => array(
                    array(
                        'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxTestSms', array(), 'alerts-ctrl'),
                        'title' => t('Отправить тестовое SMS-сообщение'),
                        'description' => t('Отправляет SMS-сообщение, на номер администратора, указанный в настройках сайта'),
                        'confirm' => t('Вы действительно хотите отправить тестовое SMS-сообщение')
                    ),
                )
            );
    }
}

