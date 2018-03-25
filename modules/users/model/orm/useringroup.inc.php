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
class UserInGroup extends \RS\Orm\AbstractObject
{
    protected static
        $table = "users_in_group";

    protected function _init()
    {
        $properties = $this->getPropertyIterator()->append(array(
            'user' => new Type\Integer(array(
                'description' => t('ID пользователя')
            )),
            'group' => new Type\Varchar(array(
                'description' => t('ID группы пользователей')
            ))
        ));
        
        $this->addIndex(array('user', 'group'), self::INDEX_PRIMARY);
    }    
    
    /**
    * Задает группы, в которых состоит пользователь
    * 
    * @param integer $userid - ID пользователя
    * @param array $groupsAlias - список групп
    */
    function linkUserToGroup($userid, array $groupsAlias)
    {
        $q = new \RS\Orm\Request();
        $q->delete()
            ->from(new \Users\Model\Orm\UserInGroup())
            ->where( array('user' => $userid) )->exec();
        foreach($groupsAlias as $group)
        {
            $this['user'] = $userid;
            $this['group'] = $group;
            $this->insert();
        }
    }
}

