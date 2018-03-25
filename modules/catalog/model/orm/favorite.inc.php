<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - избранные товары
* @ingroup Catalog
*/
class Favorite extends \RS\Orm\OrmObject
{
    protected static
        $table = 'product_favorite';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'guest_id' => new Type\Varchar(array(
                'description' => t('id гостя'),
                'maxLength' => 50
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('id пользователя')
            )),
            'product_id' => new Type\Integer(array(
                'description' => t('id товара')
            ))  
        ));
        
        $this->addIndex(array('guest_id', 'user_id', 'product_id'), self::INDEX_UNIQUE);
    }
}