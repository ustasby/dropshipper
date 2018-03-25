<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm;
use \RS\Orm\Type;

class Xdir extends \RS\Orm\AbstractObject
{
	protected static
		$table = 'product_x_dir';
		
	function _init()
	{
        $this->getPropertyIterator()->append(array(
            'product_id' => new Type\Integer(array(
                'description' => t('ID товара')
            )),
            'dir_id' => new Type\Integer(array(
                'description' => t('ID категории')
            ))
        ));
        
        $this->addIndex(array('dir_id', 'product_id'), self::INDEX_UNIQUE);
	}
}
