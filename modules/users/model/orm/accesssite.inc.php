<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;
use \RS\Orm\Type;

class AccessSite extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'access_site';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'group_alias' => new Type\Varchar(array(
                'description' => t('ID группы'),
                'maxLength' => 50
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('ID пользователя')
            )),
            'site_id' => new Type\Integer(array(
                'description' => t('ID сайта, к которому разрешен доступ')
            ))
        ));
        
        $this->addIndex(array('site_id', 'group_alias'), self::INDEX_UNIQUE);
        $this->addIndex(array('site_id', 'user_id'), self::INDEX_UNIQUE);
    }
}

