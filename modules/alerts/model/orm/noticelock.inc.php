<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Model\Orm;

use \RS\Orm\AbstractObject;
use \RS\Orm\Type;

/**
 * Класс описывает заблокированные для Desktop приложения уведомления в рамках пользователя
 */
class NoticeLock extends AbstractObject
{
    protected static
        $table = 'notice_lock';

    public function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'user_id' => new Type\Integer(array(
                'description' => t('Пользователь')
            )),
            'notice_type' => new Type\Varchar(array(
                'description' => t('Тип Desktop уведомления'),
                'maxLength' => 100
            ))
        ));

        $this->addIndex(array('site_id', 'user_id', 'notice_type'), self::INDEX_UNIQUE);
    }
}