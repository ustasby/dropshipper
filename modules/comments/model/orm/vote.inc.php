<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Comments\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - голос за комментарий
* @ingroup Comments
*/
class Vote extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'comments_votes';
    
    function _init()
    {        
        $this->getPropertyIterator()->append(array(
            'ip' => new Type\Varchar(array(
                'description' => t('IP пользователя, который оставил комментарий')
            )),
            'comment_id' => new Type\Integer(array(
                'description' => t('ID комментария')
            )),
            'help' => new Type\Integer(array(
                'description' => t('Оценка полезности комментария')
            ))
        ));
        
        $this->addIndex(array('ip', 'comment_id'), self::INDEX_UNIQUE);
    }
    
    
}

