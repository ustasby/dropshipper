<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;
use \RS\Orm\Type;

class AccessModule extends \RS\Orm\AbstractObject
{
    const
        /**
        * Величина, обозначающая максимальные права к модулю
        */
        MAX_ACCESS_RIGHTS = 255,
        FULL_MODULE_ACCESS = 'all';
    
    protected static
        $table = 'access_module';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'module' => new Type\Varchar(array(
                'description' => t('Идентификатор модуля'),
                'maxLength' => 150
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('ID пользователя')
            )),
            'group_alias' => new Type\Varchar(array(
                'description' => t('ID группы'),
                'maxLength' => 50
            )),
            'access' => new Type\Integer(array(
                'description' => t('Уровень доступа')
            ))
        ));
        
        $this->addIndex(array('site_id', 'module', 'user_id', 'group_alias'), self::INDEX_UNIQUE);
    }
}

