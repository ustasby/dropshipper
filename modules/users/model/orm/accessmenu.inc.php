<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;
use \RS\Orm\Type;

class AccessMenu extends \RS\Orm\AbstractObject
{
    const
        FULL_USER_ACCESS = -1, //Полный доступ к меню пользователя
        FULL_ADMIN_ACCESS = -2, //Полный доступ к меню администратора
        USER_MENU_TYPE = 'user', //Меню клиентской части
        ADMIN_MENU_TYPE = 'admin'; //Меню админ панели
        
    public static
        $table = 'access_menu';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'menu_id' => new Type\Varchar(array(
                'description' => t('ID пункта меню'),
                'maxLength' => 50
            )),
            'menu_type' => new Type\Enum(array('user', 'admin'), array(
                'description' => t('Тип меню'),
                'allowEmpty' => false,
                'default' => 'user'
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('ID пользователя')
            )),
            'group_alias' => new Type\Varchar(array(
                'description' => t('ID группы'),
                'maxLength' => 50
            ))
        ));
        $this->addIndex(array('site_id', 'menu_type'), self::INDEX_KEY);
    }
}

