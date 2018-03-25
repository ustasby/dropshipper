<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Users\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - связь пользователей с группами
* @ingroup Users
*/
class TryAuth extends \RS\Orm\AbstractObject
{
    protected static
        $table = "try_auth";

    protected function _init()
    {
        $properties = $this->getPropertyIterator()->append(array(
            'ip' => new Type\Varchar(array(
                'description' => t('IP-адрес'),
                'primaryKey' => true
            )),
            'total' => new Type\Integer(array(
                'description' => t('Количество попыток авторизации')
            )),
            'last_try_dateof' => new Type\Datetime(array(
                'description' => t('Дата последней попытки авторизации')
            )),
            'try_login' => new Type\Varchar(array(
                'description' => t('Логин, последней попытки авторизации')
            ))
        ));
    }
    
    function _initDefaults()
    {
        $this->setLocalParameter('checkRights', false);
    }
    
    function getPrimaryKeyProperty()
    {
        return 'ip';
    }
}