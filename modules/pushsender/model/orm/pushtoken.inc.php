<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace PushSender\Model\Orm;
use \RS\Orm\Type;

/**
* Объект связи пользователя и токена Firebase для отправки Push уведомлений.
* Клиентское устройство должно зарегистрироваться в базе Firebase Cloud Messaging и получить token.
*/
class PushToken extends \RS\Orm\OrmObject
{
    protected static
        $table = 'pushsender_user_token';
    
    function _init()
    {
        parent::_init()->append(array(
            'user_id' => new Type\Integer(array(
                'description' => t('ID пользователя'),
            )),
            'push_token' => new Type\Varchar(array(
                'description' => t('Токен пользователя в Firebase'),
                'maxlength' => 300,
            )),        
            'dateofcreate' => new Type\Datetime(array(
                'description' => t('Дата создания')
            )),
            'app' => new Type\Varchar(array(
                'description' => t('Приложение, для которого выписан token'),
                'maxLength' => 50
            )),
            'uuid' => new Type\Varchar(array(
                'description' => t('Уникальный идентификатор устройства'),
                'maxLength' => 255,
            )),
            'model' => new Type\Varchar(array(
                'maxLength' => 80,
                'description' => t('Модель устройства')
            )),
            'manufacturer' => new Type\Varchar(array(
                'maxLength' => 80,
                'description' => t('Производитель')
            )),
            'platform' => new Type\Varchar(array(
                'maxLength' => 50,
                'description' => t('Платформа на устройстве')
            )),
            'version' => new Type\Varchar(array(
                'description' => t('Версия платформы на устройстве')
            )),
            'cordova' => new Type\Varchar(array(
                'description' => t('Версия cordova js')
            )),
            'ip' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('IP адрес'),
            )),
        ));
        
        $this->addIndex(array('model', 'platform'), self::INDEX_KEY);
        $this->addIndex(array('user_id', 'push_token'), self::INDEX_UNIQUE);
        $this->addIndex(array('app', 'uuid'), self::INDEX_UNIQUE);
    }
}
