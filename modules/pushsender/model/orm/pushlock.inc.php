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
* Объект, содержащий сведения о запрете на получение push уведомлений пользователями
*/
class PushLock extends \RS\Orm\AbstractObject
{
    const
        PUSH_CLASS_ALL = 'all';
    
    protected static
        $table = 'pushsender_push_lock';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'user_id' => new Type\Integer(array(
                'description' => t('Пользователь')
            )),
            'app' => new Type\Varchar(array(
                'description' => t('Приложение'),
                'maxLength' => 100
            )),
            'push_class' => new Type\Varchar(array(
                'description' => t('Класс уведомлений, all - запретить все'),
                'maxLength' => 100
            ))
        ));
        
        $this->addIndex(array('site_id', 'user_id', 'app', 'push_class'), self::INDEX_UNIQUE);
    }
    
    function getPrimaryKeyProperty()
    {
        return array('site_id', 'user_id', 'app', 'push_class');
    }
}
