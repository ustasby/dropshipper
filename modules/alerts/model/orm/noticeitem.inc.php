<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Model\Orm;
use RS\Orm\Type;

/**
* Уведомление, которое после сможет получить Desktop программа
*/
class NoticeItem extends \RS\Orm\OrmObject
{
    protected static
        $table = 'notice_item';
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'dateofcreate' => new Type\Datetime(array(
                'description' => t('Дата создания')
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Заголовок уведомления')
            )),
            'short_message' => new Type\Varchar(array(
                'maxLength' => 400,
                'description' => t('Короткий текст уведомления')
            )),
            'full_message' => new Type\Text(array(
                'description' => t('Полный текст уведомления')
            )),
            'link' => new Type\Varchar(array(
                'description' => t('Ссылка')
            )),
            'link_title' => new Type\Varchar(array(
                'description' => t('Подпись к ссылке')
            )),
            'notice_type' => new Type\Varchar(array(
                'description' => t('Тип уведомления')
            )),
            'destination_user_id' => new Type\Integer(array(
                'description' => t('Пользователь-адресат уведомления'),
                'hint' => t('0 - означает, что уведомление адресовано всем пользователям'),
                'allowEmpty' => false,
                'visibleApp' => false
            ))
        ));
        
        $this->addIndex(array('destination_user_id', 'notice_type'));
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['dateofcreate'] = date('c');
        }
    }
}

