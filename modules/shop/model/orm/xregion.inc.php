<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

class Xregion extends \RS\Orm\AbstractObject
{
    protected static
        $table = "order_x_region";
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'zone_id' => new Type\Integer(array(
                'description' => t('ID Зоны')
            )),
            'region_id' => new Type\Integer(array(
                'description' => t('ID Региона')
            )),
        ));
        
        $this->addIndex(array('zone_id', 'region_id'), self::INDEX_UNIQUE);
    }
}

