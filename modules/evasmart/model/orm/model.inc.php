<?php
namespace Evasmart\Model\Orm;
use \RS\Orm\Type;

/**
* ORM объект
*/
class Model extends \RS\Orm\OrmObject
{
    protected static
        $table = 'testmodule_evasmart';
    
    function _init()
    {
        parent::_init()->append(array(
            'title' => new Type\Varchar(array(
                'description' => t('Название'),
            ))
        ));
    }
}
