<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model\Orm;
use \RS\Orm\Type;

/**
 * ORM объект содержит сведения о прочитанных объектах пользователя
 */
class ReadedItem extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'readed_item';

    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'user_id' => new Type\User(array(
                'description' => t('Пользователь')
            )),
            'entity' => new Type\Varchar(array(
                'description' => t('Тип прочитанного объекта'),
                'maxLength' => 50
            )),
            'entity_id' => new Type\Integer(array(
                'description' => t('ID прочитанного объекта')
            )),
            'last_id' => new Type\Integer(array(
                'description' => t('ID последнего прочитанного объекта')
            ))
        ));

        $this->addIndex(array('site_id', 'user_id', 'entity', 'entity_id'), self::INDEX_UNIQUE);
    }
}
