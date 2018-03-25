<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module;
use \RS\Orm\Type;

/**
* Объект, описывающий таблицу, в которой будут храниться настройки к модулям
*/
class ModuleConfig extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'module_config';
        
    function _init()
    {
        self::$db = \Setup::$DB_NAME;
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'module' => new Type\Varchar(array(
                'description' => t('Имя модуля'),
                'maxLength' => 150
            )),
            'data' => new Type\Text(array(
                'description' => t('Данные модуля')
            ))
        ));
        
        $this->addIndex(array('site_id', 'module'), self::INDEX_PRIMARY);
    }
}