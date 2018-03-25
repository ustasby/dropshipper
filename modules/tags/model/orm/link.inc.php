<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Tags\Model\Orm;
use \RS\Orm\Type;
                  
class Link extends \RS\Orm\OrmObject
{
    protected static
        $table = 'tags_links';
        
    function _init()
    {
        parent::_init();
        $this->getPropertyIterator()->append(array(
            'word_id' => new Type\Bigint(array(
                'description' => t('ID тега')
            )),
            'type' => new Type\Varchar(array(
                'description' => t('Тип связи'),
                'maxlength' => 20
            )),
            'link_id' => new Type\Integer(array(
                'description' => t('ID объекта, с которым связан тег')
            ))
        ));
        
        $this->addIndex(array('word_id', 'type', 'link_id'), self::INDEX_UNIQUE);
    }
    
}

