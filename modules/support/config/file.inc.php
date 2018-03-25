<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Support\Config;
use \RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    
    function _init()
    {
        parent::_init()->append(array(
            'send_admin_message_notice' => new Type\Varchar(array(
                'maxLength' => '1',
                'description' => t('Уведомлять о личном сообщении администратора'),
                'listfromarray' => array(array(
                        'Y'    => t('Да'), 
                        'N'    => t('Нет'), 
                    )
                ),
            )),
            'send_user_message_notice' => new Type\Varchar(array(
                'maxLength' => '1',
                'description' => t('Уведомлять о личном сообщении пользователя'),
                'listfromarray' => array(array(
                        'Y'    => t('Да'), 
                        'N'    => t('Нет'), 
                    )
                ),
            )),
        ));                             
    }               
}