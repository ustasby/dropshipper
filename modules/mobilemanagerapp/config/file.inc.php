<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace MobileManagerApp\Config;
use \RS\Orm\Type;

/**
* Настройки модуля
*/
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'allow_user_groups' => new Type\ArrayList(array(
                'runtime' => false,            
                'description' => t('Группы пользователей, для которых доступно данное приложение'),
                'list' => array(array('\Users\Model\GroupApi','staticSelectList')),
                'size' => 7,
                'attr' => array(array('multiple' => true))
            )),
            'push_enable' => new Type\Integer(array(
                'description' => t('Включить Push уведомления для данного приложения?'),
                'checkboxView' => array(1,0)
            ))
        ));
    }
}
