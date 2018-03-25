<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Comments\Config;
use \RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
	function _init()
	{
		parent::_init()->append(array(
            'need_moderate' => new Type\Varchar(array(
                'maxLength' => '1',
                'description' => t('Необходима премодерация'),
                'ListFromArray' => array(array(
                    'Y' => t('Да'), 
                    'N' => t('Нет'))),
                'Attr' => array(array('size' => 1)),
            )),
            'allow_more_comments' => new Type\Integer(array(
                'maxLength' => '1',
                'description' => t('Несколько комментариев с одного IP адреса'),
                'CheckboxView' => array(1,0)
            )),
            'need_authorize' => new Type\Varchar(array(
                'maxLength' => '1',
                'description' => t('Необходима авторизация'),
                'ListFromArray' => array(array(
                    'Y' => t('Да'), 
                    'N' => t('Нет')
                )),
                'Attr' => array(array('size' => 1)),
            )),
            'widget_newlist_pagesize' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Количество последних комментариев отображаемых на виджете'),
            )),
        ));
	}
}
