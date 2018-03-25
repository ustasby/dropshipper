<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля 
*/
class File extends \RS\Orm\ConfigObject
{  
    function _init()
    {
        parent::_init()->append(array(
            'dialog_open_delay' => new Type\Integer(array(
                'description' => t('Время задержки перед открытием диалог подписки'),
                'hint' => t('0 - всплывающее окно показываться не будет')
            )),
        ));
    }    
    
}