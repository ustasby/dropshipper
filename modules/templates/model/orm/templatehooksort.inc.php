<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Templates\Model\Orm;
use \RS\Orm\Type;

/**
* Объект описывает 
*/
class TemplateHookSort extends \RS\Orm\AbstractObject
{        
    protected static
        $table = 'tpl_hook_sort';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'context' => new Type\Varchar(array(
                'description' => t('Контекст темы оформления'),
                'maxLength' => 100
            )),
            'hook_name' => new Type\Varchar(array(
                'description' => t('Идентификатор хука'),
                'maxLength' => 100
            )),
            'module' => new Type\Varchar(array(
                'description' => t('Идентификатор модуля'),
                'maxLength' => 50
            )),
            'sortn' => new Type\Varchar(array(
                'description' => t('Порядковый номер'),
                'index' => true
            ))
        ));
        
        $this->addIndex(array('site_id', 'context', 'hook_name', 'module'), self::INDEX_UNIQUE);
    }
    
}
